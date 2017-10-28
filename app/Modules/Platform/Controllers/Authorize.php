<?php
/**
 * Authorize - A Controller for managing the User Authentication.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 */

namespace App\Modules\Platform\Controllers;

use Nova\Helpers\ReCaptcha;
use Nova\Http\Request;

use Nova\Support\Facades\App;
use Nova\Support\Facades\Auth;
use Nova\Support\Facades\Config;
use Nova\Support\Facades\Hash;
use Nova\Support\Facades\Redirect;
use Nova\Support\Facades\Response;
use Nova\Support\Facades\Validator;

use Shared\Support\Facades\Password;

use App\Modules\Platform\Controllers\BaseController;
use App\Modules\Platform\Models\UserToken as LoginToken;
use App\Modules\Platform\Notifications\AuthenticationToken as LoginTokenNotification;

use Carbon\Carbon;


class Authorize extends BaseController
{
    /**
     * The currently used Layout.
     *
     * @var mixed
     */
    protected $layout = 'Default';


    /**
     * Display the login view.
     *
     * @return \Nova\View\View
     */
    public function login()
    {
        return $this->createView()
            ->shares('title', __d('platform', 'User Login'));
    }

    /**
     * Handle a POST request to login the User.
     *
     * @return \Nova\Http\RedirectResponse
     */
    public function postLogin(Request $request)
    {
        // Verify the submitted reCAPTCHA
        if(! ReCaptcha::check($request->input('g-recaptcha-response'), $request->ip())) {
            $status = __d('platform', 'Invalid reCAPTCHA submitted.');

            return Redirect::back()->withStatus($status, 'danger');
        }

        // Retrieve the Authentication credentials.
        $credentials = $request->only('username', 'password');

        // Prepare the 'remember' parameter.
        $remember = $request->has('remember');

        // Make an attempt to login the Guest with the given credentials.
        if(! Auth::attempt($credentials, $remember)) {
            // An error has happened on authentication.
            $status = __d('platform', 'Wrong username or password.');

            return Redirect::back()->withStatus($status, 'danger');
        }

        // The User is authenticated now; retrieve his Model instance.
        $user = Auth::user();

        if (Hash::needsRehash($user->password)) {
            $password = $credentials['password'];

            $user->password = Hash::make($password);

            // Save the User Model instance - used with the Extended Auth Driver.
            $user->save();
        }

        if ($user->activated == 0) {
            Auth::logout();

            // User not activated; go logout and redirect him to account activation page.
            return Redirect::to('register/verify')
                ->withInput(array('email' => $user->email))
                ->withStatus(__d('platform', 'Please activate your Account!'), 'danger');
        }

        // Redirect to the User's Dashboard.
        return Redirect::intended('dashboard')
            ->withStatus(__d('platform', '<b>{0}</b>, you have successfully logged in.', $user->username), 'success');
    }

    /**
     * Handle a GET request to logout the current User.
     *
     * @return \Nova\Http\RedirectResponse
     */
    public function logout()
    {
        Auth::logout();

        // Redirect to the login page.
        $guard = Config::get('auth.defaults.guard', 'web');

        $uri = Config::get("auth.guards.{$guard}.authorize", 'login');

        return Redirect::to($uri)->withStatus( __d('platform', 'You have successfully logged out.'), 'success');
    }

    /**
     * Display the token request view.
     *
     * @return \Nova\View\View
     */
    public function tokenRequest()
    {
        return $this->createView()
            ->shares('title', __d('platform', 'One-Time Login'))
            ->shares('guard', 'web');
    }

    /**
     * Handle a POST request to token request.
     *
     * @return \Nova\Http\RedirectResponse
     */
    public function tokenProcess(Request $request)
    {
        Validator::extend('recaptcha', function($attribute, $value, $parameters) use ($request)
        {
            return ReCaptcha::check($value, $request->ip());
        });

        $validator = Validator::make(
            $input = $request->only('email', 'g-recaptcha-response'),
            array(
                'email'                => 'required|email|exists:users',
                'g-recaptcha-response' => 'required|recaptcha'
            ),
            array(
                'recaptcha' => __d('platform', 'The reCaptcha verification failed. Try again.'),
            )
        );

        if ($validator->fails()) {
            return Redirect::back()->withStatus($validator->errors(), 'danger');
        }

        $token = LoginToken::uniqueToken();

        $loginToken = LoginToken::create(array(
            'email' => $input['email'],
            'token' => $token,
        ));

        $hashKey = Config::get('app.key');

        $timestamp = time();

        $hash = hash_hmac('sha256', $token .'|' .$request->ip() .'|' .$timestamp, $hashKey);

        $loginToken->user->notify(new LoginTokenNotification($hash, $timestamp, $token));

        return Redirect::back()
            ->withStatus(__d('platform', 'Login instructions have been sent to the Center email address.'), 'success');
    }

    /**
     * Handle a login on token request.
     *
     * @return \Nova\Http\RedirectResponse
     */
    public function tokenLogin(Request $request, $hash, $timestamp, $token)
    {
        $maxAttempts = Config::get('platform::throttle.maxAttempts', 5);
        $lockoutTime = Config::get('platform::throttle.lockoutTime', 1); // In minutes.

        // Compute the throttle key.
        $throttleKey = 'users.tokenLogin|' .$request->ip();

        // Make a Rate Limiter instance, via Container.
        $limiter = App::make('Nova\Cache\RateLimiter');

        if ($limiter->tooManyAttempts($throttleKey, $maxAttempts, $lockoutTime)) {
            $seconds = $limiter->availableIn($throttleKey);

            return Redirect::to('authorize')
                ->withStatus(__d('platform', 'Too many login attempts, please try again in {0} seconds.', $seconds), 'danger');
        }

        $validity = Config::get('platform::tokenLogin.validity', 15); // In minutes.

        $oldest = Carbon::parse('-' .$validity .' minutes');

        //
        $hashKey = Config::get('app.key');

        $data = $token .'|' .$request->ip() .'|' .$timestamp;

        if (! hash_equals($hash, hash_hmac('sha256', $data, $hashKey)) || ($timestamp <= $oldest->timestamp)) {
            $limiter->hit($throttleKey, $lockoutTime);

            return Redirect::to('authorize')
                ->withStatus(__d('platform', 'Link is invalid, please request a new link.'), 'danger');
        }

        try {
            $loginToken = LoginToken::with('user')
                ->where('token', $token)
                ->where('created_at', '>', $oldest)
                ->firstOrFail();
        }
        catch (ModelNotFoundException $e) {
            $limiter->hit($throttleKey, $lockoutTime);

            return Redirect::to('authorize')
                ->withStatus(__d('platform', 'Link is invalid, please request a new link.'), 'danger');
        }

        $limiter->clear($throttleKey);

        // Delete all stored login Tokens for this Center.
        LoginToken::where('email', $loginToken->email)->delete();

        // Authenticate the Center instance.
        Auth::login($loginToken->user, true /* remember */);

        return Redirect::to('dashboard')
            ->withStatus(__d('platform', '<b>{0}</b>, you have successfully logged in.', $loginToken->user->username), 'success');
    }
}

<?php

namespace Modules\Fields\Types;

use Nova\Http\Request;
use Nova\Support\Facades\View;

use Modules\Fields\Models\Field;
use Modules\Fields\Models\MetaData as MetaItem;


abstract class Type
{
    /**
     * The type handled by this Type class.
     *
     * @var string|null
     */
    protected $type;

    /**
     * MetaData model instance.
     *
     * @var \Modules\Fields\Models\MetaData
     */
    protected $model;

    /**
     * The partial View used for editor rendering.
     *
     * @var string
     */
    protected $view = 'Editor/Default';


    /**
     * Constructor.
     *
     * @param \Modules\Fields\Models\MetaData|null $model
     */
    public function __construct(MetaItem $model = null)
    {
        $this->model = $model;
    }

    /**
     * Execute the cleanup when MetaData instance is saved or deleted.
     *
     * @param bool $force
     * @return string
     */
    public function cleanup($force = false)
    {
        //
    }

    /**
     * Gets a rendered form of the value.
     *
     * @param array $data
     * @return string
     */
    public function render(array $data = array())
    {
        return $this->get();
    }

    /**
     * Gets a rendered form of the editor.
     *
     * @param \Modules\Fields\Models\Field $field
     * @param mixed $value
     * @return string
     */
    public function renderForEditor(Field $field, $value = null)
    {
        return View::make($this->getView(), compact('field', 'value'), 'Fields')->render();
    }

    /**
     * Gets the type handled by this Type class.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Gets the model instance.
     *
     * @return \Modules\Fields\Models\MetaData|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Gets the View used for rendering the editor.
     *
     * @return string
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Parse & return the meta item value.
     *
     * @return mixed
     */
    public function get()
    {
        if (isset($this->model)) {
            return $this->model->getRawValue();
        }
    }

    /**
     * Parse & set the meta item value.
     *
     * @param mixed $value
     */
    public function set($value)
    {
        if (isset($this->model)) {
            $this->model->setRawValue($value);
        }
    }

    /**
     * Assertain whether we can handle the type of variable passed.
     *
     * @param  mixed  $value
     * @return bool
     */
    abstract public function isType($value);

    /**
     * Get the types class name.
     *
     * @return string
     */
    public function getClass()
    {
        return get_class($this);
    }

    /**
     * Output value to string.
     *
     * @return string
     */
    public function toString()
    {
        return serialize($this->get());
    }

    /**
     * Output value to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}

<?php

namespace Drickferreira\BootstrapForms;

use Collective\Html\FormBuilder as IlluminateFormBuilder;

class FormBuilder extends IlluminateFormBuilder
{

    /**
     * An array containing the currently opened form groups.
     *
     * @var array
     */
    protected $groupStack = [];

    /**
     * An array containing the options of the currently open form groups.
     *
     * @var array
     */
    protected $groupOptions = [];
    protected $formConfig = [];
    protected $opened = false;

    /**
     * Bootstrap Form default config
     * class: form css class
     * columns: number of form columns
     * labelWidth: width of label element in Bootstrap grid units
     * objectWidth: width of div container of form element in Bootstrap grid units
     */

    protected $defaultConfig = array(
        'default' => array(
                'class' => '',
                'columns' => 1,
                'labelWidth' => 0,
                'objectWidth' => 0,
                'labelOptions' => [],
                'objectOptions' => [],
            ),
        'inline' => array(
                'class' => 'form-inline',
                'columns' => 1,
                'labelWidth' => 0,
                'objectWidth' => 0,
                'labelOptions' => [],
                'objectOptions' => [],
            ),
        'horizontal' => array(
                'class' => 'form-horizontal',
                'columns' => 1,
                'labelWidth' => 2,
                'objectWidth' => 10,
                'labelOptions' => [],
                'objectOptions' => [],
            ),
        '2column' => array(
                'class' => 'form-horizontal',
                'columns' => 2,
                'labelWidth' => 2,
                'objectWidth' => 4,
                'labelOptions' => [],
                'objectOptions' => [],
            ),
        );

    /**
     * Horizontal
     */

    public function loadConfig($config = 'default')
    {
        $this->formConfig = array_get($this->defaultConfig, $config, 'default');
    }

    public function config(array $config = [])
    {
        $this->formConfig = array(
                'class' => isset($config['class']) ? $config['class'] : '',
                'columns' => isset($config['columns']) ? $config['columns'] : 1,
                'labelWidth' => isset($config['labelWidth']) ? $config['labelWidth'] : 0,
                'objectWidth' => isset($config['objectWidth']) ? $config['objectWidth'] : '',
                'labelOptions' => isset($config['labelOptions']) ? $config['labelOptions'] : [],
                'objectOptions' => isset($config['objectOptions']) ? $config['objectOptions'] : [],
            );
    }

    public function open(array $options = [])
    {
        $method = array_get($options, 'method', 'post');

        if($this->formConfig == []){
            $this->loadConfig();
        }
        $this->opened = true;

        $options = $this->appendClassToOptions($this->formConfig['class'], $options);

        // We need to extract the proper method from the attributes. If the method is
        // something other than GET or POST we'll use POST since we will spoof the
        // actual method since forms don't support the reserved methods in HTML.
        $attributes['method'] = $this->getMethod($method);
        $attributes['action'] = $this->getAction($options);
        $attributes['accept-charset'] = 'UTF-8';

        // If the method is PUT, PATCH or DELETE we will need to add a spoofer hidden
        // field that will instruct the Symfony request to pretend the method is a
        // different method than it actually is, for convenience from the forms.
        $append = $this->getAppendage($method);

          if (isset($options['files']) && $options['files']) {
              $options['enctype'] = 'multipart/form-data';
          }

        // Finally we're ready to create the final form HTML field. We will attribute
        // format the array of attributes. We will also add on the appendage which
        // is used to spoof requests for this PUT, PATCH, etc. methods on forms.
        $attributes = array_merge(

          $attributes, array_except($options, $this->reserved)

        );

        // Finally, we will concatenate all of the attributes into a single string so
        // we can build out the final form open statement. We'll also append on an
        // extra value for the hidden _method field if it's needed for the form.
        $attributes = $this->html->attributes($attributes);

          return '<form'.$attributes.'>'.$append;
    }



    /**
     * Open a new form group.
     *
     * @param  string $name
     * @param  mixed  $label
     * @param  array  $options
     * @param  array  $labelOptions
     *
     * @return string
     */
    public function openGroup(
        $name,
        $options = []
    ) {
        $options = $this->appendClassToOptions('form-group', $options);

        // Append the name of the group to the groupStack.
        $this->groupStack[] = $name;

        $this->groupOptions[] = $options;

        // Check to see if error blocks are enabled
        if ($this->errorBlockEnabled($options)) {
            if ($this->hasErrors($name)) {
                // If the form element with the given name has any errors,
                // apply the 'has-error' class to the group.
                $options = $this->appendClassToOptions('has-error', $options);
            }
        }

        $attributes = [];
        foreach ($options as $key => $value) {
            if (!in_array($key, ['errorBlock'])) {
                $attributes[$key] = $value;
            }
        }

        return '<div' . $this->html->attributes($attributes) . '>';// . $label;
    }

    /**
     * Close out the last opened form group.
     *
     * @return string
     */
    public function closeGroup()
    {
        // Get the last added name from the groupStack and
        // remove it from the array.
        $name = array_pop($this->groupStack);

        // Get the last added options to the groupOptions
        // This way we can check if error blocks were enabled
        $options = array_pop($this->groupOptions);

        // Append the errors to the group and close it out.
        return '</div>';
    }

    /**
     * Create a form input field.
     *
     * @param  string $type
     * @param  string $name
     * @param  string $value
     * @param  array  $options
     *
     * @return string
     */
    public function input($type, $name, $value = null, $options = [])
    {
        //Capture label
        $label = $type != 'hidden' ? $this->getLabel($name, $options) : '';

        //Capture Bootstrap classes
        $class = $this->getClasses($name,$options);

        // Don't add form-control for some input types (like submit, checkbox, radio)
        if (!in_array($type, ['hidden', 'submit', 'checkbox', 'radio', 'reset', 'file'])) {
            $options = $this->appendClassToOptions('form-control', $options);
        } 

        // Call the parent input method so that Laravel can handle
        // the rest of the input set up.
        $object = parent::input($type, $name, $value, $options);

        if ($type == 'hidden' )
        {
            return $object;
        }

        return $this->wrapObject($name, $label,$class,$object);

    }

    public function staticElement($name, $value = null, $options = [])
    {
        //Capture label
        $label = $this->getLabel($name, $options);

        //Capture Bootstrap classes
        $class = $this->getClasses($name,$options);
        $class = $this->appendClassToOptions('form-control-static', $class);
        $class['name'] = $name;
        $class['id'] = $name;

        // Call the parent input method so that Laravel can handle
        // the rest of the input set up.
        $object = '<p'. $this->html->attributes($class) .'>'. $value.'</p>';

        return $this->wrapObject($name, $label,$class,$object);

    }


    /**
     * Create a select box field.
     *
     * @param  string $name
     * @param  array  $list
     * @param  string $selected
     * @param  array  $options
     *
     * @return string
     */
    public function select($name, $list = [], $selected = null, $options = [])
    {
        //Capture label
        $label = $this->getLabel($name, $options);

        //Capture Bootstrap classes
        $class = $this->getClasses($name,$options);

        $options = $this->appendClassToOptions('form-control', $options);

        // Call the parent select method so that Laravel can handle
        // the rest of the select set up.
        $object = parent::select($name, $list, $selected, $options);

        return $this->wrapObject($name, $label,$class,$object);

    }

    /**
     * Create a plain form input field.
     *
     * @param  string $type
     * @param  string $name
     * @param  string $value
     * @param  array  $options
     *
     * @return string
     */
    public function plainInput($type, $name, $value = null, $options = [])
    {
        return parent::input($type, $name, $value, $options);
    }

    /**
     * Create a plain select box field.
     *
     * @param  string $name
     * @param  array  $list
     * @param  string $selected
     * @param  array  $options
     *
     * @return string
     */
    public function plainSelect(
        $name,
        $list = [],
        $selected = null,
        $options = []
    ) {
        return parent::select($name, $list, $selected, $options);
    }

    /**
     * Create a checkable input field.
     *
     * @param  string $type
     * @param  string $name
     * @param  mixed  $value
     * @param  bool   $checked
     * @param  array  $options
     *
     * @return string
     */
    protected function checkable($type, $name, $value, $checked, $options)
    {

        $checked = $this->getCheckedState($type, $name, $value, $checked);

        if ($checked) {
            $options['checked'] = 'checked';
        }

        return parent::input($type, $name, $value, $options);

    }

    /**
     * Create a checkbox input field.
     *
     * @param  string $name
     * @param  mixed  $value
     * @param  bool   $checked
     * @param  array  $options
     *
     * @return string
     */
    public function checkbox(
        $name,
        $value = 1,
        $checked = null,
        $options = []
    ) {
        //Capture label
        $label = $this->getLabel($name, $options, '');

        //Capture Bootstrap classes
        $class = $this->getClasses($name,$options);
        $class = $this->appendClassToOptions('checkbox', $class);

        $object = parent::checkbox($name, $value, $checked, $options);

        return $label != '' ? $this->wrapCheckable($label, $class, $object) : $this->wrapObject($name,$label,$class,$object);
    }

    /**
     * Create a radio button input field.
     *
     * @param  string $name
     * @param  mixed  $value
     * @param  mixed  $label
     * @param  bool   $checked
     * @param  array  $options
     *
     * @return string
     */
    public function radio(
        $name,
        $value = null,
        $label = null,
        $checked = null,
        $options = []
    ) {
        //Capture label
        $label = $this->getLabel($name, $options, false);

        //Capture Bootstrap classes
        $class = $this->getClasses($name,$options);
        $class = $this->appendClassToOptions('radio', $class);

        $object = parent::radio($name, $value, $checked, $options);

        return $this->wrapCheckable($label, $class, $object);

    }

    /**
     * Create an inline checkbox input field.
     *
     * @param  string $name
     * @param  mixed  $value
     * @param  mixed  $label
     * @param  bool   $checked
     * @param  array  $options
     *
     * @return string
     */
    public function inlineCheckbox(
        $name,
        $value = 1,
        $label = null,
        $checked = null,
        $options = []
    ) {

        //Capture Bootstrap classes
        $class = $this->getClasses($name,$options);
        $class = $this->appendClassToOptions('checkbox-inline', $class);

        $object = parent::checkbox($name, $value, $checked, $options);

        return $this->wrapCheckable($label, $class, $object);

    }

    /**
     * Create an inline radio button input field.
     *
     * @param  string $name
     * @param  mixed  $value
     * @param  mixed  $label
     * @param  bool   $checked
     * @param  array  $options
     *
     * @return string
     */
    public function inlineRadio(
        $name,
        $value = null,
        $label = null,
        $checked = null,
        $options = []
    ) {
        //Capture label
        $label = $this->getLabel($name, $options, false);

        //Capture Bootstrap classes
        $class = $this->getClasses($name,$options);
        $class = $this->appendClassToOptions('radio-inline', $class);

        $object = parent::radio($name, $value, $checked, $options);

        return $this->wrapCheckable($label, $class, $object);

    }

    /**
     * Create a textarea input field.
     *
     * @param  string $name
     * @param  string $value
     * @param  array  $options
     *
     * @return string
     */
    public function textarea($name, $value = null, $options = [])
    {
        //Capture label
        $label = $type != 'hidden' ? $this->getLabel($name, $options) : '';

        //Capture Bootstrap classes
        $class = $this->getClasses($name,$options);

        $options = $this->appendClassToOptions('form-control', $options);

        $object = parent::textarea($name, $value, $options);

        return $this->wrapObject($name,$label,$class,$object);
    }

    /**
     * Create a plain textarea input field.
     *
     * @param  string $name
     * @param  string $value
     * @param  array  $options
     *
     * @return string
     */
    public function plainTextarea($name, $value = null, $options = [])
    {
        return parent::textarea($name, $value, $options);
    }

    /**
     * Append the given class to the given options array.
     *
     * @param  string $class
     * @param  array  $options
     *
     * @return array
     */
    private function appendClassToOptions($class, array $options = [])
    {
        // If a 'class' is already specified, append the 'form-control'
        // class to it. Otherwise, set the 'class' to 'form-control'.
        $options['class'] = isset($options['class']) ? $options['class'] . ' '
            : '';
        $options['class'] .= $class;

        return $options;
    }

    /**
     * Determine whether the form element with the given name
     * has any validation errors.
     *
     * @param  string $name
     *
     * @return bool
     */
    private function hasErrors($name)
    {
        if (is_null($this->session) || !$this->session->has('errors')) {
            // If the session is not set, or the session doesn't contain
            // any errors, the form element does not have any errors
            // applied to it.
            return false;
        }

        // Get the errors from the session.
        $errors = $this->session->get('errors');

        // Check if the errors contain the form element with the given name.
        // This leverages Laravel's transformKey method to handle the
        // formatting of the form element's name.

        return $errors->has($this->transformKey($name));
    }

    /**
     * Get the formatted errors for the form element with the given name.
     *
     * @param  string $name
     *
     * @return string
     */
    private function getFormattedErrors($name)
    {
        if (!$this->hasErrors($name)) {
            // If the form element does not have any errors, return
            // an emptry string.
            return '';
        }

        // Get the errors from the session.
        $errors = $this->session->get('errors');

        // Return the formatted error message, if the form element has any.
        return $errors->first($this->transformKey($name),
            '<p class="help-block">:message</p>');
    }

    /**
     * Wrap the given object in the necessary wrappers.
     *
     * @param  mixed $label
     * @param  array $options
     * @param  string $object
     *
     * @return string
     */
    private function wrapObject($name, $label, $options, $object)
    {
        $errors = '';
        // Check to see if we are to include the formatted help block
        if ($this->errorBlockEnabled($options)) {
            // Get the formatted errors for this form group.
            $errors = $this->getFormattedErrors($name);
        }

        return $label. '<div '. $this->html->attributes($options).'>'.$object.$errors.'</div>';
    }


    /**
     * Wrap the given checkable in the necessary wrappers.
     *
     * @param  mixed $label
     * @param  array $options
     * @param  string $object
     *
     * @return string
     */
    private function wrapCheckable($label, $option, $object)
    {
        return '<div '. $this->html->attributes($option). '><label>' . $object . ' ' . $label
        . '</label></div>';
    }

    /**
     * errorBlockEnabled
     *
     * @param array $options
     *
     * @return bool
     * @author  Vincent Sposato <Vincent.Sposato@gmail.com>
     * @version v1.0
     */
    private function errorBlockEnabled($options = [])
    {
        // Check to see if errorBlock key exists
        if (array_key_exists("errorBlock", $options)) {
            // Return the value from the array
            return $options["errorBlock"];
        }

        // Default to true if it does not exist
        return true;
    }

    private function getOption($name, array &$options = [], $val = false)
    {
        if (array_has($options, $name))
        {
            $val = array_get($options, $name);
            array_forget($options, $name);
        } 
        return $val;
    }

    private function getLabel($name, array &$options = [], $wrap = true)
    {
        $label = $this->getOption('label', $options);
        if (!$label) return ''; 
        if (!$wrap) return $label;

        $labelOptions = $this->getOption('labelOptions', $options) ? $this->getOption('labelOptions', $options) : array_get($this->formConfig, 'labelOptions');

        $class = $this->getOption('labelClass', $labelOptions);
        if (!$class)
        {
            $labelWidth = array_get($this->formConfig, 'labelWidth');
            if($labelWidth>0) 
                $class = 'col-md-'.$labelWidth.' control-label';
        }

        $labelOptions = $this->appendClassToOptions($class, $labelOptions);

        return $this->label($name, $label, $labelOptions);

    }

    private function getClasses($name, array &$options = [])
    {
        if (!$this->opened) return [];
        $returnOptions = [];
        $offset = $this->getOption('offset', $options, 0);
        $returnOptions = $offset > 0 ? $this->appendClassToOptions('col-md-offset-'.$offset, $returnOptions) : [];

        $objectWidth = $this->formConfig['objectWidth'];
        $labelWidth = $this->formConfig['labelWidth'];
        $extend = $this->getOption('extend', $options);

        if ($extend == 'group'){
            $objectWidth += $labelWidth;
        } else if ($extend == 'full'){
            $aux = 0;
            for ($i = 0; $i < $this->formConfig['columns']; $i++){
                $aux += $objectWidth + $labelWidth;
            }
            if (in_array($name, $this->labels))
                $aux -= $labelWidth;
            $objectWidth = $aux;
        } else if ($extend > 1 && $extend < 12){
            $objectWidth += $extend;
        } 

        $objectWidth = $objectWidth > 12 ? 12 : $objectWidth;

        $widthClass = $objectWidth>0 ? 'col-md-'.$objectWidth : '';

        $returnOptions = $this->appendClassToOptions($widthClass, $returnOptions);

        return $returnOptions;
    }

}

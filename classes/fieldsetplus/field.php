<?php
/**
 * Fieldsetplus
 *
 * @anthor     Yasuhiro Hayashida https://github.com/hayashida
 * @copyright  2012 Yasuhiro Hayashida
 * @license    MIT License
 */

namespace Fieldsetplus;

class Fieldsetplus_Field extends \Fuel\Core\Fieldset_Field
{
    protected $group = '';

    /**
     * Constructor
     *
     * @param  string
     * @param  string
     * @param  array
     * @param  array
     * @param  string
     * @param  Fieldset
     */
    public function __construct($name, $label = '', array $attributes = array(), array $rules = array(), $group = '', $fieldset = null)
    {
        parent::__construct($name, $label, $attributes, $rules, $fieldset);
        $this->group = $group;
        
        return $this;
    }
    
    /**
     * Build the field
     *
     * @return  string
     */
    public function build_field()
    {
        $form = $this->fieldset()->form();

        // Add IDs when auto-id is on
        if ($form->get_config('auto_id', false) === true and $this->get_attribute('id') == '')
        {
            $auto_id = $form->get_config('auto_id_prefix', '')
                .str_replace(array('[', ']'), array('-', ''), $this->name);
            $this->set_attribute('id', $auto_id);
        }

        switch( ! empty($this->attributes['tag']) ? $this->attributes['tag'] : $this->type)
        {
            case 'hidden':
                $build_field = $form->hidden($this->name, $this->value, $this->attributes);
                break;
            case 'radio': case 'checkbox':
                if ($this->options)
                {
                    $build_field = array();
                    $i = 0;
                    foreach ($this->options as $value => $label)
                    {
                        $attributes = $this->attributes;
                        $attributes['name'] = $this->name;
                        $this->type == 'checkbox' and $attributes['name'] .= '['.$i.']';

                        $attributes['value'] = $value;
                        $attributes['label'] = $label;

                        if (is_array($this->value) ? in_array($value, $this->value) : $value == $this->value)
                        {
                            $attributes['checked'] = 'checked';
                        }

                        if( ! empty($attributes['id']))
                        {
                            $attributes['id'] .= '_'.$i;
                        }
                        else
                        {
                            $attributes['id'] = null;
                        }

                        $build_field[$form->label($label, $attributes['id'])] = $this->type == 'radio'
                            ? $form->radio($attributes)
                            : $form->checkbox($attributes);

                        $i++;
                    }
                }
                else
                {
                    $build_field = $this->type == 'radio'
                        ? $form->radio($this->name, $this->value, $this->attributes)
                        : $form->checkbox($this->name, $this->value, $this->attributes);
                }
                break;
            case 'select':
                $attributes = $this->attributes;
                $name = $this->name;
                unset($attributes['type']);
                array_key_exists('multiple', $attributes) and $name .= '[]';
                $build_field = $form->select($name, $this->value, $this->options, $attributes);
                break;
            case 'textarea':
                $attributes = $this->attributes;
                unset($attributes['type']);
                $build_field = $form->textarea($this->name, $this->value, $attributes);
                break;
            case 'button':
                $build_field = $form->button($this->name, $this->value, $this->attributes);
                break;
            case false:
                $build_field = '';
                break;
            default:
                $build_field = $form->input($this->name, $this->value, $this->attributes);
                break;
        }

        if (empty($build_field) or $this->type == 'hidden')
        {
            return $build_field;
        }

        return $this->_template($build_field);
    }
    
    protected function _template($build_field)
    {
        $form = $this->fieldset()->form();

        $required_mark = $this->get_attribute('required', null) ? $form->get_config('required_mark', null) : null;
        $label = $this->label ? $form->label($this->label, null, array('for' => $this->get_attribute('id', null))) : '';
//         $error_template = $form->get_config('error_template', '');
//         $error_msg = ($form->get_config('inline_errors') && $this->error()) ? str_replace('{error_msg}', $this->error(), $error_template) : '';
//         $error_class = $this->error() ? $form->get_config('error_class') : '';
        $error_template = '';
        $error_msg = '';
        $error_class = '';

        if (is_array($build_field))
        {
            $label = $this->label ? $form->label($this->label) : '';
            $template = $this->template ?: $form->get_config('multi_field_template', "\t\t<tr>\n\t\t\t<td class=\"{error_class}\">{group_label}{required}</td>\n\t\t\t<td class=\"{error_class}\">{fields}\n\t\t\t\t{field} {label}<br />\n{fields}\t\t\t{error_msg}\n\t\t\t</td>\n\t\t</tr>\n");
            if ($template && preg_match('#\{fields\}(.*)\{fields\}#Dus', $template, $match) > 0)
            {
                $build_fields = '';
                foreach ($build_field as $lbl => $bf)
                {
                    $bf_temp = str_replace('{label}', $lbl, $match[1]);
                    $bf_temp = str_replace('{required}', $required_mark, $bf_temp);
                    $bf_temp = str_replace('{field}', $bf, $bf_temp);
                    $build_fields .= $bf_temp;
                }

                $template = str_replace($match[0], '{fields}', $template);
                $template = str_replace(array('{group_label}', '{required}', '{fields}', '{error_msg}', '{error_class}', '{description}'), array($label, $required_mark, $build_fields, $error_msg, $error_class, $this->description), $template);

                return $template;
            }

            // still here? wasn't a multi field template available, try the normal one with imploded $build_field
            $build_field = implode(' ', $build_field);
        }

        $template = $this->template ?: $form->get_config('group_field_template', "{label} {field} {description}");
        $template = str_replace(array('{label}', '{field}', '{description}'),
            array($label, $build_field, $this->description),
            $template);
        return trim($template).' ';
    }
}
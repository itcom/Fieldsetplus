<?php

namespace Fieldsetplus;

class Fieldsetplus extends \Fuel\Core\Fieldset
{
	protected $fieldset_groups = array();
	
	public function remove($name)
	{
		unset($this->fields[$name]);
		return $this;
	}
	
	public function add_group($name, $label, $parts)
	{
		if ($name == '' or isset($this->fieldset_groups[$name])) {
			throw new \RuntimeException('Groupname empty or already exists in this Group: "' . $name . '".');
		}
		
		$fields = array();
		foreach ($parts as $part) {
			$tmp_name = $part[0];
			$tmp_label = isset($part[1]) ? $part[1] : '';
			$tmp_attributes = isset($part[2]) ? $part[2] : array();
			$tmp_rules = isset($part[3]) ? $part[3] : array();
			
			$fields[] = $this->add($tmp_name, $tmp_label, $tmp_attributes, $tmp_rules, $name);
		}
		
		$this->fieldset_groups[$name] = array(
											'name' => $name,
											'label' => $label,
											'fields' => $fields,
											'sources' => array()
										);
	}
	
	public function add($name, $label = '', array $attributes = array(), array $rules = array(), $group = '')
	{
		if ($name instanceof Fieldsetplus_Field)
		{
			if ($name->name == '' or $this->field($name->name) !== false)
			{
				throw new \RuntimeException('Fieldname empty or already exists in this Fieldset: "'.$name->name.'".');
			}

			$name->set_fieldset($this);
			$this->fields[$name->name] = $name;
			return $name;
		}
		elseif ($name instanceof Fieldsetplus)
		{
			if (empty($name->name) or $this->field($name->name) !== false)
			{
				throw new \RuntimeException('Fieldset name empty or already exists in this Fieldset: "'.$name->name.'".');
			}

			$name->set_parent($this);
			$this->fields[$name->name] = $name;
			return $name;
		}

		if (empty($name) || (is_array($name) and empty($name['name'])))
		{
			throw new \InvalidArgumentException('Cannot create field without name.');
		}

		// Allow passing the whole config in an array, will overwrite other values if that's the case
		if (is_array($name))
		{
			$attributes = $name;
			$label = isset($name['label']) ? $name['label'] : '';
			$rules = isset($name['rules']) ? $name['rules'] : array();
			$name = $name['name'];
		}

		// Check if it exists already, if so: return and give notice
		if ($field = $this->field($name))
		{
			\Error::notice('Field with this name exists already in this fieldset: "'.$name.'".');
			return $field;
		}

		$this->fields[$name] = new \Fieldsetplus_Field($name, $label, $attributes, $rules, $group, $this);

		return $this->fields[$name];
	}
	
	public function build($action = null)
	{
		$attributes = $this->get_config('form_attributes');
		if ($action and ($this->fieldset_tag == 'form' or empty($this->fieldset_tag)))
		{
			$attributes['action'] = $action;
		}

		$open = ($this->fieldset_tag == 'form' or empty($this->fieldset_tag))
			? $this->form()->open($attributes).PHP_EOL
			: $this->form()->{$this->fieldset_tag.'_open'}($attributes);

		$fields_output = '';
		foreach ($this->field() as $f)
		{
			if (array_key_exists($f->group, $this->fieldset_groups) === false) {
				in_array($f->name, $this->disabled) or $fields_output .= $f->build().PHP_EOL;
			} else {
				if (!in_array($f->name, $this->disabled)) {
					$this->fieldset_groups[$f->group]['sources'][] = $f->build_field();
					if (preg_match('/\{group\_'.$f->group.'\}/', $fields_output) === 0) {
						$fields_output .= '{group_'.$f->group.'}';
					}
				}
			}
		}
		
		foreach ($this->fieldset_groups as $group) {
			$required_mark = null;
			$error_template = $this->form->get_config('group_error_template', '<div>{error_msg}</div>');
			$error_msg = '';
			$error_class = '';
			foreach ($group['fields'] as $field) {
				$required_mark = $field->get_attribute('required', null) ? $this->form->get_config('required_mark', null) : $required_mark;
				$error_msg .= ($this->form->get_config('inline_errors') && $field->error()) ? str_replace('{error_msg}', $field->error(), $error_template) : '';
				$error_class = $field->error() ? $this->form->get_config('error_class') : $error_class;
			}
			$sources = implode('', $group['sources']);
			
			if ($group['label'] !== '') {
				$template = $this->form->get_config('field_template', "\t\t<tr>\n\t\t\t<td class=\"{error_class}\">{label}{required}</td>\n\t\t\t<td class=\"{error_class}\">{field} {description} {error_msg}</td>\n\t\t</tr>\n");
				$template = str_replace(
								array('{label}', '{required}', '{field}', '{error_msg}', '{error_class}', '{description}'),
								array($group['label'], $required_mark, $sources, $error_msg, $error_class, ''),
								$template);
			} else {
				$template = $this->form()->get_config('group_template', "\t\t<tr>\n\t\t\t<td class=\"{error_class}\" colspan=\"2\">\n{field}\n\t\t\t</td>\n\t\t</tr>\n");
				$template = str_replace(
								array('{field}', '{error_class}'),
								array($sources, $error_class),
								$template);
			}
			$fields_output = str_replace('{group_'.$group['name'].'}', $template, $fields_output);
		}

		$close = ($this->fieldset_tag == 'form' or empty($this->fieldset_tag))
			? $this->form()->close($attributes).PHP_EOL
			: $this->form()->{$this->fieldset_tag.'_close'}($attributes);

		$template = $this->form()->get_config((empty($this->fieldset_tag) ? 'form' : $this->fieldset_tag).'_template',
			"\n\t\t{open}\n\t\t<table>\n{fields}\n\t\t</table>\n\t\t{close}\n");
		$template = str_replace(array('{form_open}', '{open}', '{fields}', '{form_close}', '{close}'),
			array($open, $open, $fields_output, $close, $close),
			$template);

		return $template;
	}
}
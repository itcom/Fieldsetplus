<?php
/**
 * Fieldsetplus
 * 
 * @anthor     Yasuhiro Hayashida https://github.com/hayashida
 * @copyright  2012 Yasuhiro Hayashida
 * @license    MIT License
 */

return array(
    'group_template' => "\t\t<tr>\n\t\t\t<td class=\"field_groups {error_class}\" colspan=\"2\">\n\t\t\t\t{field}\n\t\t\t</td>\n\t\t</tr>\n",
    'group_field_template' => "{label} {field} {description}",
// 	'group_multi_field_template'  => "{group_label} {fields} {field} {label}<br />\n{fields}<span>{description}</span>",
	'group_error_template' => "<div>{error_msg}</div>",
);
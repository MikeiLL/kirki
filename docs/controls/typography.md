# typography

The `typography` field allows you to add the most important typography-related controls in a single, compact view.
It shows the following controls:

* font-family
* variant
* subset
* font-size
* line-height
* letter-spacing
* color

Example:

```php
Kirki::add_field( 'my_config', array(
	'type'        => 'typography',
	'settings'    => 'my_setting',
	'label'       => esc_attr__( 'Control Label', 'kirki' ),
	'section'     => 'my_section',
	'default'     => array(
		'font-family'    => 'Roboto',
		'variant'        => '400',
		'font-size'      => '14px',
		'line-height'    => '1.5',
		'letter-spacing' => '0',
		'color'          => '#333333',
	),
	'priority'    => 10,
	'output'      => array(
		array(
			'element' => 'body',
		),
	),
) );
```
## Defining active sub-fields

You can define which of the above fields you want displayed or hidden using the `default` argument of the field.

Since defining a default value is **mandatory** for all fields, this way you can control both the default value of the field and the fields it contains in as little code as possible.

If for example you wanted to hide the `line-height` and `letter-spacing` controls, you'd delete these 2 lines from the defaults specified in the above example.

The exception to the above rule is the `variant` and `subset` controls.

Since the `typography` control uses google fonts, in order to ensure that your fonts will be properly enqueued we have to add the variant & subset controls for google fonts.
So if you add `font-family`, all 3 controls will be automatically displayed when the selected font requires it.

## Output

The `typography` field requires you to use only the `element` argument in order to properly generate its CSS.
Of course you can define multiple elements as documented in the documentation of the `output` argument, but you do not have to define a `property` since it will be automatically applies for each sub-element of the control.

## Variants

The available options for variants are:

* `'100'`
* `'100italic'`
* `'200'`
* `'200italic'`
* `'300'`
* `'300italic'`
* `'regular'`
* `'italic'`
* `'500'`
* `'500italic'`
* `'600'`
* `'600italic'`
* `'700'`
* `'700italic'`
* `'800'`
* `'800italic'`
* `'900'`
* `'900italic'`

When selecting a default value for the variant, please make sure that the value is valid for the selected google font.
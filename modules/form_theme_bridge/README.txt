
A) Using the bridge for a form:

1) Assign form_theme_bridge as the theme for the form: e.g. 
  $form['#theme'] = 'form_theme_bridge';
  
2) Assign the theming function you want the bridge to call to layout the form: e.g. 
  $form['#bridge_to_theme'] = 'theme_function_name';
Note: the theme (theme_function_name) should take a single argument: an array named 'data'.

3) In your theming function or tpl file, be sure to print the hidden functions required by
the form (form_id, form_build_id, and form_token). Those elements will be included in the 
data passed to the theme set in #bridge_to_theme. An easier way to deal with this is to use
form_theme_bridge_parse_data_for_layout. It will store all three fields in $required_hidden_fields
(see below for more on this function).


B) Conventions for structuring iterable data in form api definition

1) #iterable
If the form contains iterable data, create an array containing the following cells: each 
iteration as a cell, and an additional cell with a key of #iterable and a value of TRUE:

  $form['iterations'] => array(
    '#iterable => TRUE,
    'iterator_0' => $iteration_0,
    'iterator_1' => $iteration_1,
    'iterator_2' => $iteration_2,
  );

The #iterable attribute tells the bridge function to preserve that array structure so it will be 
available for use by the layout theming function or template. Each iteration will contain one
or more form elements. Note that the key names 'iterations', 'iterator_0', etc. are purely
for example purposes and can be replaced with whatever key names make sense.

2) base__$id
When constructing iterable data in the form definition function, use the following approach to 
differentiate input element names from one iteration to the next. Start with a base name for an 
input field: e.g. cancel. Choose a relevant field to use so as to give each iteration a unique ID. 
Concatenate the base name and the ID, separating them with two underscores: 

  $iteration_0['cancel__' . $id] = array(
    '#type' => 'checkbox',
    '#default_value' => FALSE, 
  );

This convention is assumed by the parsing mechanism in the bridge function. Following this convention 
allows the bridge function to output data in a structure which is easy to iterate through in a layout  
theming function or template, while also providing each input field with a unique name, as is needed
for proper form submission handling. Note that parsing based on the double underscore may be needed 
in the form's validation or submit functions (see D below).

Warning: do not use double underscores anywhere else in form element names or bad things may happen.


C) Helper function for theming.

When the bridge function calls the layout theming function set in #bridge_to_theme, it 
passes in a single array. It's top level will include all top level elements from the form
array, and an array for each instance of #iterable. For example:

  $data = array(
    'form_id' => 'the element as html',
    'form_build_id' => 'the element as html',
    'form_token => 'the element as html''
    'some_element_name' => 'the element as html',
    'some_other_name' => 'the element as html',
    'iterations' => array(
      '27' => array(
        'some_iterating_element_name' => 'the element as html',
        'some_other_iterating_element_name' => 'the element as html',
      ),
      '52' => array(
        'some_iterating_element_name' => 'the element as html',
        'some_other_iterating_element_name' => 'the element as html',
      ),
    );
  );

Using the above, a layout theming function can output top-level elements like this:

<?php print $data['form_id'] ?>
<?php print $data['some_element_name'] ?>

Similarly, the layout theming function can print out top-level elements by name, and can
loop through the iterable array, laying out elements which are consistently named: e.g. 
some_iterating_element_name appears in each iteration. For example:

foreach ($data['iterations'] as $iteration) {
  <?php print $iteration['some_iterating_element_name'] ?>
}
  
This is probably fine for a theming function, but could be made more convenient for a tpl
file. Adding a preprocessor function for the tpl file, and having it call 
form_theme_bridge_parse_data_for_layout will simplify things for the tpl file. It does so
in two ways.

Primarily, it pulls out the elements from $data, and makes them top level elements in
$variables. This allows calls such as these:

<?php print $some_element_name ?>
foreach ($iterations as $iteration) {
  <?php print $iteration['some_iterating_element_name'] ?>
}

Secondarily, it takes the form's standard hidden fields, and concatenates them into a
single variable: $required_hidden_fields.


D) Helper function for submitted form data

One inconvenience of working with iterable data is the need for unique names for form 
elements can make it more difficult to write validate or submit functions. If the form 
from the example in section C above were submitted, keys such as the following would be 
found in $form_state['values']:

some_iterating_element_name__27
some_iterating_element_name__52
some_other_iterating_element_name__27
some_other_iterating_element_name__52

Properly processing this data would require first parsing it into the parts separated 
by the double underscore: i.e. back into its base and ID. The function 
form_theme_bridge_clean_submitted_values is provided as a utility which performs that 
parsing. 

To use the function, pass in $form_state, and a string telling the function how to order 
iterable data. The string should be either 'id' or 'base'. The function will clean up the 
data, and then use it to replace $form_state['values'].

Ordering the above example by base would result in the following:

$form_state['values'] = array(
  'some_iterating_element_name' => array(
    '27' => 'the submitted data',
    '52' => 'the submitted data',
  ),
  'some_other_iterating_element_name' => array(
    '27' => 'the submitted data',
    '52' => 'the submitted data',
  ),
);

Whereas ordering it by id would result in the following:

$form_state['values'] = array(
  '27' => array(
    'some_iterating_element_name' => 'the submitted data',
    'some_other_iterating_element_name' => 'the submitted data',
  ),
  '52' => array(
    'some_iterating_element_name' => 'the submitted data',
    'some_other_iterating_element_name' => 'the submitted data',
  ),
);

All regular data in $form_state['values'] will be preserved by the function.
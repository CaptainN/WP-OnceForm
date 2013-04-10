<?php
include 'OnceForm/OnceForm.php';

/*
The OnceForm - Write once HTML5 forms processing for PHP.

Copyright (C) 2012-2013  adcSTUDIO LLC

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

/**
 * Automatically adds and validates nonce with no boilerplate.
 */
class WP_OnceForm extends OnceForm
{
	protected $action;
	protected $nonce_name;

	public function __construct( $form_func = NULL, $validator = NULL, $action = 'oncenonce' )
	{
		if ( is_callable( $form_func ) )
			$this->form_func = $form_func;
		else
			$this->form_html = $form_func;

		$this->user_validator = $validator;

		$this->action = $action;

		if ( !is_null( $form_func ) )
			$this->init();
	}

	public function init()
	{
		if ( is_callable( $this->form_func ) )
			$this->capture_form( $this->form_func );

		$this->parse_form();
		$this->extract_fields();

		// inserts the nonce fiels must happen after extract_fields
		$this->insert_nonce( $this->action );

		// get the request data
		$data = $this->get_request_data();

		// verify, and set this new data
		$this->set_data( $data );

		if ( $this->isRequest )
			$this->isValid = $this->validate();
	}

	/**
	 * Adds WP nonce field to the OnceForm.
	 */
	protected function insert_nonce( $action, $name = '_wponcenonce', $referer = false )
	{
		$this->action = $action;
		$this->nonce_name = $name;

		// get the nonce fields
		$nonce = wp_nonce_field( $action, $name, $referer, false );

		// parse into nodes, so we can manipulate
		$encoding = mb_detect_encoding( $nonce );
		$doc = new DOMDocument( '', $encoding );

		$doc->loadHTML( '<html><head>
		<meta http-equiv="content-type" content="text/html; charset='.
		$encoding.'"></head><body>' . trim( $nonce ) . '</body></html>' );

		// grab the new elements
		$xpath = new DOMXPath( $doc );
		$nodes = $xpath->query('//input[@name]');

		foreach( $nodes as $node )
		{
			// monkey patch the `required` flag for each
			$node->setAttribute( 'required', 'required' );

			// finally, add the elements
			$node = $this->doc->importNode( $node );
			$this->form->appendChild( $node );

			// add a oncefield for the nonce field
			if ( $node->getAttribute('name') == $name )
				$this->fields[] = new InputField( $node,
					new NonceFieldType( $name ) );
		}
	}

	/**
	 * Checks the PHP GP objects, to see if a request has been made.
	 * Called automatically in init. Also strips out the WP enforced
	 * magic quotes.
	 */
	public function get_request_data() {
		return stripslashes_deep( parent::get_request_data() );
	}
}

class NonceFieldType extends SubFieldType
{
	public $nodes;

	public function __construct( $name )
	{
		// note: enumerable is set false here.
		parent::__construct( 'input', 'hidden', 'NonceField', 'NonceValidator',
			false, "//input[@type='hidden' and @name='$name']"
		);
		$this->nodes = $nodes;
	}
}

class NonceValidator extends OnceValidator
{
	public $action = -1;

	public function __construct( $props = NULL, $action = 'oncenonce' )
	{
		parent::__construct( $props );

		$this->action = $action;
	}

	public function isValid()
	{
		$valid = parent::isValid();

		if ( !wp_verify_nonce( $this->field->value(), $this->action ) )
			$this->errors[] = 'Invalid WP nonce';

		return $this->isValid = empty( $this->errors ) && $valid;
	}

}

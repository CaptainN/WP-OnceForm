<?php
class WP_MetaForm
{
	public $rawform;
	public $prefix = '';
	public $validator;
	public $label = 'OnceForm MetaBox';
	public $post_type = 'page';

	protected $onceform;

	public function __construct( $options, /* Callable */ $form, $validator = null )
	{
		$this->rawform = $form;
		$this->validator = $validator;

		if ( is_array( $options ) ) {
			if ( !empty( $options['prefix'] ) )
				$this->prefix = $options['prefix'];
			if ( !empty( $options['label'] ) )
				$this->label = $options['label'];
			if ( !empty( $options['post_type'] ) )
				$this->post_type = $options['post_type'];
			// Only wire up the save_meta handler if autosave is true,
			// or not set (to default to true).
			if ( empty( $options['autosave'] ) )
				add_action( 'save_post', array( &$this, 'save_meta') );
		}
	}

	public function metabox()
	{
		if ( current_user_can( 'manage_options' ) ) {
			add_meta_box( $this->prefix.'_meta_box',
				$this->label,
				array( &$this, 'admin_meta_box' ), $this->post_type,
				'normal', 'high'
			);
		}
	}

	private function init_onceform()
	{
		if ( !isset( $this->onceform ) ) {
			$this->onceform = new WP_OnceForm(
				$this->rawform,
				$this->validator
			);
			//var_dump($this->onceform->is_request());
			//var_dump($this->onceform->data);
		}
	}

	public function admin_meta_box( $post )
	{
		$this->init_onceform();

		$fields = $this->onceform->get_field_names();

		// load saved values from field names
		$data = array();
		foreach( $fields as $field ) {
			$fieldname = $this->prefix . $field;
			$data[ $field ] = get_post_meta( $post->ID, $fieldname,	true );
		}

		// set saved forms values
		$this->onceform->set_data( $data, false );

		echo $this->onceform;
	}

	public function save_meta( $post_id )
	{
		if ( ! current_user_can( 'manage_options' )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		) return;

		if ( !isset($_POST['post_type']) || $_POST['post_type'] != $this->post_type ) return;

		// Onceform will not be available yet - so make it so
		$this->init_onceform();

		// save the onceform data
		foreach( $this->onceform->data as $meta_key => $meta_data ) {
			update_post_meta( $post_id, $this->prefix.$meta_key, $meta_data );
		}
	}

}

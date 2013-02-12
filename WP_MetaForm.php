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

		if ( is_array( $options ) )
		{
			if ( !empty( $options['prefix'] ) )
				$this->prefix = $options['prefix'];
			if ( !empty( $options['label'] ) )
				$this->label = $options['label'];
			if ( !empty( $options['post_type'] ) )
				$this->post_type = $options['post_type'];
		}
	}

	public function metabox()
	{
		if ( current_user_can( 'manage_options' ) )
		{
			add_meta_box( $this->prefix.'_meta_box',
				$this->label,
				array( &$this, 'admin_meta_box' ), $this->post_type,
				'normal', 'high'
			);
		}
	}

	private function init_onceform()
	{
		$this->onceform = new WP_OnceForm(
			$this->rawform,
			$this->validator
		);
	}

	function admin_meta_box( $post )
	{
		$this->init_onceform();

		$fields = $this->onceform->get_field_names();

		$data = array();
		foreach( $fields as $field )
		{
			$fieldname = $this->prefix . $field;
			$data[ $field ] = get_post_meta( $post->ID, $fieldname,	true );
		}

		// set default forms values
		$this->onceform->resolve_request( $data );

		echo $this->onceform;
	}

	function save_post( $post_id )
	{
		if ( ! current_user_can( 'manage_options' )
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		) return;

		$screen = get_current_screen();
		if ( $screen->post_type != self::POST_TYPE ) return;

		// Onceform will not be available yet - so make it so
		$this->init_onceform();

		$meta_keys = array();
		foreach( $this->onceform->data as $meta_key => $meta_data )
		{
			$meta_keys[] = $meta_key;
			update_post_meta( $post_id, self::POST_META_PREFIX.$meta_key, $meta_data );
		}

		update_post_meta( $post_id, self::POST_META_KEYS, $meta_keys );
	}
}

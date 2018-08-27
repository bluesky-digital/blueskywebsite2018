(function($){
	window.et_dashboard_all_editors_configs = {};

	window.et_dashboard_tinymce_wrapper = function( args ) {
		var editor_id = args.editor_selector;

		tinymce.init( args );

		// store all the tinymce configurations on page to restore it when needed
		et_dashboard_all_editors_configs[ editor_id ] = args;

		// add Text/Visual mode switcher
		setTimeout( function(){
			var $option_container = $( '.' + editor_id ).closest( 'li' );

			if ( 0 !== $option_container.length ) {
				$option_container.prepend( '<div class="et_dashboard_mce_mode_switcher" data-editor_id="' + editor_id + '"><span class="et_dashboard_mce_visual et_dashboard_mce_switch et_dashboard_mce_active">' + dashboardEditor.visual + '</span><span class="et_dashboard_mce_text et_dashboard_mce_switch">' + dashboardEditor.text + '</span></div>' );
			}

			// Switch Editor modes
			$( 'body' ).on( 'click', '.et_dashboard_mce_switch', function() {
				var $this_el = $( this );
				var $parent_container = $this_el.closest( '.et_dashboard_mce_mode_switcher' );
				var current_editor_id = $parent_container.data( 'editor_id' );
				var switch_to = $this_el.hasClass( 'et_dashboard_mce_visual' ) ? 'visual' : 'text';

				if ( ! current_editor_id ) {
					return;
				}

				if ( 'visual' === switch_to ) {
					if ( typeof et_dashboard_all_editors_configs[ current_editor_id ] === 'undefined' ) {
						return;
					}

					tinymce.init( et_dashboard_all_editors_configs[ current_editor_id ] );
				} else {
					var current_editor = current_editor_id ? tinymce.EditorManager.get( current_editor_id ) : false;

					if ( current_editor ) {
						current_editor.remove();
					}
				}

				$parent_container.find( '.et_dashboard_mce_switch' ).removeClass( 'et_dashboard_mce_active' );
				$this_el.addClass( 'et_dashboard_mce_active' );
			} );
		}, 100);
	}
})(jQuery)
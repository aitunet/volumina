/**
 * The Chapter list block, in the editor.
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Disabled } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';
import BookSelect from '../shared/book-select';

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const postId = useSelect(
		( select ) => select( editorStore )?.getCurrentPostId(),
		[]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Chapter list', 'volumina' ) }>
					<BookSelect
						value={ attributes.bookId }
						onChange={ ( bookId ) => setAttributes( { bookId } ) }
						help={ __(
							'Leave this as it is when the block sits in an audiobook template.',
							'volumina'
						) }
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Running times', 'volumina' ) }
						help={ __( 'How long each chapter runs.', 'volumina' ) }
						checked={ !! attributes.showDurations }
						onChange={ ( showDurations ) =>
							setAttributes( { showDurations } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Disabled>
					<ServerSideRender
						block={ metadata.name }
						attributes={ attributes }
						urlQueryArgs={ postId ? { post_id: postId } : {} }
					/>
				</Disabled>
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,

	save() {
		return null;
	},
} );

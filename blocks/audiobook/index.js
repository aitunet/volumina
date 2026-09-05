/**
 * The Audiobook block, in the editor.
 *
 * The preview is the server's own render, so what an editor sees is what a
 * listener gets. There is no second implementation to keep in step.
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

/**
 * The parts a listener can be shown, and what to call them.
 *
 * A function rather than a constant so the labels are translated when the
 * block is drawn, not when the file is first evaluated.
 */
const parts = () => [
	[ 'showCover', __( 'Cover', 'volumina' ) ],
	[ 'showDetails', __( 'Details', 'volumina' ) ],
	[ 'showPlayer', __( 'Player', 'volumina' ) ],
	[ 'showChapters', __( 'Chapter list', 'volumina' ) ],
];

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const postId = useSelect(
		( select ) => select( editorStore )?.getCurrentPostId(),
		[]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Audiobook', 'volumina' ) }>
					<BookSelect
						value={ attributes.bookId }
						onChange={ ( bookId ) => setAttributes( { bookId } ) }
						help={ __(
							'Leave this as it is when the block sits in an audiobook template.',
							'volumina'
						) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Parts to show', 'volumina' ) }>
					{ parts().map( ( [ key, label ] ) => (
						<ToggleControl
							__nextHasNoMarginBottom
							key={ key }
							label={ label }
							checked={ !! attributes[ key ] }
							onChange={ ( value ) =>
								setAttributes( { [ key ]: value } )
							}
						/>
					) ) }

					{ attributes.showChapters && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Chapters heading', 'volumina' ) }
							checked={ !! attributes.showHeading }
							onChange={ ( showHeading ) =>
								setAttributes( { showHeading } )
							}
						/>
					) }
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

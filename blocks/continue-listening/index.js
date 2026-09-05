/**
 * The Continue listening block, in the editor.
 *
 * The preview shows the editor's own listening, because that is the only
 * listener the server can answer for. A visitor sees their own.
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	Disabled,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Continue listening', 'volumina' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Books to show', 'volumina' ) }
						value={ attributes.count }
						onChange={ ( count ) =>
							setAttributes( { count: count || 1 } )
						}
						min={ 1 }
						max={ 10 }
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Covers', 'volumina' ) }
						help={ __(
							'The cover of each book, small.',
							'volumina'
						) }
						checked={ !! attributes.showCovers }
						onChange={ ( showCovers ) =>
							setAttributes( { showCovers } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Disabled>
					<ServerSideRender
						block={ metadata.name }
						attributes={ attributes }
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

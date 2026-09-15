/**
 * The presentation panel: template, cover position and accent colour.
 *
 * Shared by the Audiobook and Chapter list blocks. Every control has a "site
 * setting" choice, which is what a block starts on: the block only says
 * something when it wants to differ from the site.
 */

import { useSettings } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * A colour beside the accent control, so the choice can be seen as well as read.
 *
 * @param {Object} props
 * @param {string} props.color A hex colour, or empty for none.
 */
function Swatch( { color } ) {
	return (
		<span
			aria-hidden="true"
			style={ {
				display: 'inline-block',
				width: '1em',
				height: '1em',
				verticalAlign: '-0.15em',
				marginInlineEnd: '0.4em',
				borderRadius: '50%',
				border: '1px solid rgba(0,0,0,.25)',
				background: color || 'transparent',
			} }
		/>
	);
}

/**
 * The panel.
 *
 * @param {Object}   props
 * @param {Object}   props.attributes    The block's attributes.
 * @param {Function} props.setAttributes Sets them.
 * @param {boolean}  props.cover         Whether this block places a cover, and so offers its position.
 */
export default function PresentationControls( {
	attributes,
	setAttributes,
	cover = false,
} ) {
	// Printed before this script by the server; see Blocks\Registry.
	const templates = Array.isArray( window.voluminaTemplates )
		? window.voluminaTemplates
		: [];
	const [ palette ] = useSettings( 'color.palette' );
	const colors = ( Array.isArray( palette ) ? palette : [] ).filter(
		( entry ) =>
			entry && entry.slug && /^#[0-9a-f]{3,6}$/i.test( entry.color )
	);
	const chosen = colors.find( ( entry ) => entry.slug === attributes.accent );

	return (
		<PanelBody title={ __( 'Presentation', 'volumina' ) }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Template', 'volumina' ) }
				value={ attributes.template || '' }
				options={ [
					{ value: '', label: __( 'Site setting', 'volumina' ) },
					...templates.map( ( template ) => ( {
						value: template.id,
						label: template.label,
					} ) ),
				] }
				onChange={ ( template ) => setAttributes( { template } ) }
			/>

			{ cover && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Cover', 'volumina' ) }
					value={ attributes.coverPosition || '' }
					options={ [
						{ value: '', label: __( 'Site setting', 'volumina' ) },
						{
							value: 'side',
							label: __( 'Beside the details', 'volumina' ),
						},
						{
							value: 'top',
							label: __( 'Above the details', 'volumina' ),
						},
						{ value: 'none', label: __( 'Not shown', 'volumina' ) },
					] }
					onChange={ ( coverPosition ) =>
						setAttributes( { coverPosition } )
					}
				/>
			) }

			<SelectControl
				__nextHasNoMarginBottom
				label={
					<>
						<Swatch color={ chosen ? chosen.color : '' } />
						{ __( 'Accent colour', 'volumina' ) }
					</>
				}
				value={ attributes.accent || '' }
				options={ [
					{ value: '', label: __( 'Site setting', 'volumina' ) },
					{ value: 'none', label: __( 'None', 'volumina' ) },
					...colors.map( ( entry ) => ( {
						value: entry.slug,
						label: entry.name || entry.slug,
					} ) ),
				] }
				onChange={ ( accent ) => setAttributes( { accent } ) }
				help={ __(
					'From the theme’s palette. It colours the play button, the progress bar and the current chapter, never text.',
					'volumina'
				) }
			/>
		</PanelBody>
	);
}

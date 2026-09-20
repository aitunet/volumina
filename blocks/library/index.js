/**
 * The Audiobook list block, in the editor.
 *
 * The preview is the server's own render, so what the editor sees is what
 * the page shows. The panel decides which books, how many, in what order,
 * and what each row says.
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	Disabled,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';
import PresentationControls from '../shared/presentation';

/**
 * A select over a taxonomy's terms, shown only when there are any.
 *
 * @param {Object}   props
 * @param {string}   props.taxonomy The taxonomy's name.
 * @param {string}   props.label    The control's label.
 * @param {number}   props.value    The chosen term id, or zero.
 * @param {Function} props.onChange Receives the new term id.
 */
function TermSelect( { taxonomy, label, value, onChange } ) {
	const terms = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'taxonomy', taxonomy, {
				per_page: 100,
				orderby: 'name',
				order: 'asc',
				hide_empty: false,
				context: 'view',
			} ),
		[ taxonomy ]
	);

	if ( ! Array.isArray( terms ) || terms.length === 0 ) {
		return null;
	}

	return (
		<SelectControl
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label={ label }
			value={ String( value || 0 ) }
			options={ [
				{ value: '0', label: __( 'All', 'volumina' ) },
				...terms.map( ( term ) => ( {
					value: String( term.id ),
					label: term.name,
				} ) ),
			] }
			onChange={ ( next ) => onChange( parseInt( next, 10 ) || 0 ) }
		/>
	);
}

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Audiobook list', 'volumina' ) }>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Books to show', 'volumina' ) }
						value={ attributes.count }
						onChange={ ( count ) =>
							setAttributes( { count: count || 1 } )
						}
						min={ 1 }
						max={ 50 }
					/>

					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Order', 'volumina' ) }
						value={ attributes.orderBy || 'title' }
						options={ [
							{
								value: 'title',
								label: __( 'By title', 'volumina' ),
							},
							{
								value: 'date',
								label: __( 'Newest first', 'volumina' ),
							},
						] }
						onChange={ ( orderBy ) => setAttributes( { orderBy } ) }
					/>

					<TermSelect
						taxonomy="volumina_genre"
						label={ __( 'Genre', 'volumina' ) }
						value={ attributes.genre }
						onChange={ ( genre ) => setAttributes( { genre } ) }
					/>

					<TermSelect
						taxonomy="volumina_series"
						label={ __( 'Series', 'volumina' ) }
						value={ attributes.series }
						onChange={ ( series ) => setAttributes( { series } ) }
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

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Narrator', 'volumina' ) }
						checked={ !! attributes.showNarrator }
						onChange={ ( showNarrator ) =>
							setAttributes( { showNarrator } )
						}
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Running time', 'volumina' ) }
						checked={ !! attributes.showDuration }
						onChange={ ( showDuration ) =>
							setAttributes( { showDuration } )
						}
					/>
				</PanelBody>

				<PresentationControls
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
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

/**
 * The book chooser every book block needs.
 */

import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Chooses which audiobook a block is about.
 *
 * Zero means "whichever book this page is already about", which is what an
 * Audiobook block in a single book template wants and what a block on an
 * unrelated page has to be told.
 *
 * @param {Object}   props          Component props.
 * @param {number}   props.value    The chosen book, or 0.
 * @param {Function} props.onChange Called with the new book id.
 * @param {string}   props.help     Help text under the control.
 */
export default function BookSelect( { value, onChange, help } ) {
	const books = useSelect( ( select ) => {
		return select( coreStore ).getEntityRecords(
			'postType',
			'volumina_book',
			{
				per_page: 100,
				orderby: 'title',
				order: 'asc',
				status: [ 'publish', 'draft', 'pending', 'private' ],
				context: 'edit',
				_fields: 'id,title',
			}
		);
	}, [] );

	const options = [
		{ label: __( 'The book this page is about', 'volumina' ), value: 0 },
	].concat(
		( books || [] ).map( ( book ) => ( {
			label:
				decodeEntities(
					book.title?.raw || book.title?.rendered || ''
				) || __( '(no title)', 'volumina' ),
			value: book.id,
		} ) )
	);

	return (
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Audiobook', 'volumina' ) }
			help={ help }
			value={ value }
			options={ options }
			onChange={ ( next ) => onChange( parseInt( next, 10 ) || 0 ) }
		/>
	);
}

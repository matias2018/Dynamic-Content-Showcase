// src/edit.js
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, CheckboxControl, Spinner, ToggleControl } from '@wordpress/components'; // Added CheckboxControl, Spinner, ToggleControl
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element'; // Added useState, useEffect
import './editor.scss';

// Define DCSB_TEXT_DOMAIN if not globally available in JS context (usually it's picked up by build tools)
const TEXT_DOMAIN = 'dcsb';

export default function Edit({ attributes, setAttributes, clientId }) {
    const { 
        postType, 
        numberOfPosts, 
        orderBy, 
        order, 
        selectedTaxonomy, 
        selectedTerms,
        showExcerpt,
		showThumbnail
    } = attributes;

    const blockProps = useBlockProps();

    // --- Post Type Selection ---
    const postTypes = useSelect((select) => {
        const { getPostTypes } = select('core');
        const excluded = ['attachment', 'wp_block', 'wp_navigation', 'wp_template', 'wp_template_part', 'nav_menu_item'];
        const allPostTypes = getPostTypes({ per_page: -1 });
        if (!allPostTypes) return [];
        return allPostTypes
            .filter(pt => !excluded.includes(pt.slug) && pt.viewable)
            .map(pt => ({ label: pt.name, value: pt.slug }));
    }, []);


    // --- Taxonomy Selection ---
    const taxonomies = useSelect((select) => {
        if (!postType) return [];
        const { getTaxonomies } = select('core');
        const allTaxonomies = getTaxonomies({ per_page: -1 });
        if (!allTaxonomies) return [];
        return allTaxonomies
            .filter(tax => tax.types.includes(postType)) // Filter by current postType
            .map(tax => ({ label: tax.name, value: tax.slug }));
    }, [postType]); // Re-fetch when postType changes

    // --- Term Selection ---
    const [termsLoading, setTermsLoading] = useState(false);
    const terms = useSelect((select) => {
        if (!selectedTaxonomy) return [];
        const { getEntityRecords } = select('core');
        // Note: 'kind' is 'taxonomy', 'name' is the taxonomy slug
        const query = { per_page: -1, hide_empty: true };
        const records = getEntityRecords('taxonomy', selectedTaxonomy, query);
        return records ? records.map(term => ({ label: term.name, value: term.id })) : [];
    }, [selectedTaxonomy]); // Re-fetch when selectedTaxonomy changes

    // Effect to monitor if terms are loading (getEntityRecords might return null initially)
    useEffect(() => {
        if (selectedTaxonomy) {
            const { hasFinishedResolution, isResolving } = wp.data.select('core'); // Direct wp.data usage for resolver status
            const selectorArgs = ['getEntityRecords', 'taxonomy', selectedTaxonomy, { per_page: -1, hide_empty: true }];
            
            if (isResolving('getEntityRecords', ['taxonomy', selectedTaxonomy, { per_page: -1, hide_empty: true }])) {
                 setTermsLoading(true);
            } else if (hasFinishedResolution('getEntityRecords', ['taxonomy', selectedTaxonomy, { per_page: -1, hide_empty: true }])) {
                 setTermsLoading(false);
            }
        } else {
            setTermsLoading(false);
        }
    }, [selectedTaxonomy, terms]); // Rerun when selectedTaxonomy or terms themselves change (after fetch)


    const onToggleTerm = (termId) => {
        const newSelectedTerms = selectedTerms.includes(termId)
            ? selectedTerms.filter(id => id !== termId)
            : [...selectedTerms, termId];
        setAttributes({ selectedTerms: newSelectedTerms });
    };

    // IDs for a11y
    const postTypeSelectId = `dcsb-post-type-select-${clientId}`;
    const numberPostsRangeId = `dcsb-number-posts-range-${clientId}`;
    const orderBySelectId = `dcsb-orderby-select-${clientId}`;
    const orderSelectId = `dcsb-order-select-${clientId}`;
    const taxonomySelectId = `dcsb-taxonomy-select-${clientId}`;
    const showExcerptToggleId = `dcsb-show-excerpt-toggle-${clientId}`;
	const showThumbnailToggleId = `dcsb-show-thumbnail-toggle-${clientId}`;


    const orderByOptions = [ /* ... as before ... */ ];
    const orderOptions = [ /* ... as before ... */ ];


    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Content Settings', TEXT_DOMAIN)}>
                    {/* ... Post Type, Number of Posts, Order By, Order controls as before, using unique IDs ... */}
                    <SelectControl
                        label={__('Content Type', TEXT_DOMAIN)}
                        labelPosition="top"
                        value={postType}
                        options={[{ label: __('Select a Post Type', TEXT_DOMAIN), value: '' }, ...postTypes]}
                        onChange={(value) => {
                            setAttributes({ 
                                postType: value, 
                                selectedTaxonomy: '', // Reset taxonomy when post type changes
                                selectedTerms: []    // Reset terms
                            });
                        }}
                        id={postTypeSelectId}
                    />
                    {/* ... Other controls from before ... */}
                     <RangeControl
                        label={__('Number of items', TEXT_DOMAIN)}
                        value={numberOfPosts}
                        onChange={(value) => setAttributes({ numberOfPosts: value })}
                        min={1} max={20} id={numberPostsRangeId}
                    />
                    <SelectControl
                        label={__('Order By', TEXT_DOMAIN)} value={orderBy}
                        options={orderByOptions}
                        onChange={(value) => setAttributes({ orderBy: value })} id={orderBySelectId}
                    />
                    <SelectControl
                        label={__('Order Direction', TEXT_DOMAIN)} value={order}
                        options={orderOptions}
                        onChange={(value) => setAttributes({ order: value })}
                        disabled={orderBy === 'rand'} id={orderSelectId}
                    />
					<ToggleControl
						label={__('Show Excerpt', TEXT_DOMAIN)}
						checked={!!showExcerpt}
						onChange={(value) => setAttributes({ showExcerpt: value })}
						id={showExcerptToggleId}
					/>
					<ToggleControl // <<< ADD THIS WHOLE CONTROL
						label={__('Show Featured Image', TEXT_DOMAIN)}
						checked={!!showThumbnail}
						onChange={(value) => setAttributes({ showThumbnail: value })}
						id={showThumbnailToggleId}
					/>
                </PanelBody>

                {postType && ( // Only show taxonomy panel if a post type is selected
                    <PanelBody title={__('Taxonomy Filter', TEXT_DOMAIN)} initialOpen={false}>
                        <SelectControl
                            label={__('Filter by Taxonomy', TEXT_DOMAIN)}
                            labelPosition="top"
                            value={selectedTaxonomy}
                            options={[{ label: __('Select a Taxonomy', TEXT_DOMAIN), value: '' }, ...taxonomies]}
                            onChange={(value) => {
                                setAttributes({ selectedTaxonomy: value, selectedTerms: [] }); // Reset terms on tax change
                            }}
                            help={__('Leave blank to not filter by taxonomy.', TEXT_DOMAIN)}
                            id={taxonomySelectId}
                        />

                        {selectedTaxonomy && termsLoading && <Spinner />}
                        
                        {selectedTaxonomy && !termsLoading && terms && terms.length > 0 && (
                            <div className="dcsb-terms-checklist" role="group" aria-labelledby={`dcsb-terms-label-${clientId}`}>
                                <p id={`dcsb-terms-label-${clientId}`} className="components-base-control__label">
                                    {__('Select Terms', TEXT_DOMAIN)}
                                </p>
                                {terms.map(term => (
                                    <CheckboxControl
                                        key={term.value}
                                        label={term.label}
                                        checked={selectedTerms.includes(term.value)}
                                        onChange={() => onToggleTerm(term.value)}
                                        id={`dcsb-term-${term.value}-${clientId}`}
                                    />
                                ))}
                            </div>
                        )}
                        {selectedTaxonomy && !termsLoading && (!terms || terms.length === 0) && (
                            <p>{__('No terms found for this taxonomy.', TEXT_DOMAIN)}</p>
                        )}
                    </PanelBody>
                )}
                 <PanelBody title={__('Display Settings', TEXT_DOMAIN)} initialOpen={false}>
                    <ToggleControl
                        label={__('Show Excerpt', TEXT_DOMAIN)}
                        checked={!!showExcerpt} // Ensure it's a boolean
                        onChange={(value) => setAttributes({ showExcerpt: value })}
                        id={showExcerptToggleId}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                {/* ... Editor preview message as before ... */}
                 <p>
                    {__('Dynamic Content Showcase: Displaying ', TEXT_DOMAIN)}
                    <strong>{postTypes?.find(pt => pt.value === postType)?.label || postType || __('content', TEXT_DOMAIN)}</strong>.
                </p>
                 {selectedTaxonomy && (
                    <p>
                        {__('Filtered by taxonomy: ', TEXT_DOMAIN)}
                        <strong>{taxonomies?.find(tax => tax.value === selectedTaxonomy)?.label || selectedTaxonomy}</strong>
                        {selectedTerms.length > 0 && ` (${selectedTerms.length} ${selectedTerms.length === 1 ? __('term', TEXT_DOMAIN) : __('terms', TEXT_DOMAIN)})`}
                    </p>
                )}
                <p>
                    <em>{__('Settings are in the block sidebar. Content is rendered on the frontend.', TEXT_DOMAIN)}</em>
                </p>
            </div>
        </>
    );
}
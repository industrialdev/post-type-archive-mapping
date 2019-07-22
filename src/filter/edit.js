/**
 * External dependencies
 */
import moment from 'moment';
import classnames from 'classnames';
import axios from 'axios';

var HtmlToReactParser = require('html-to-react').Parser;

const {Component, Fragment} = wp.element;

const {__} = wp.i18n;

const {decodeEntities} = wp.htmlEntities;

const {
    PanelBody,
    Placeholder,
    QueryControls,
    RangeControl,
    SelectControl,
    Spinner,
    TextControl,
    ToggleControl,
    Toolbar,
} = wp.components;

const {
    InspectorControls,
    BlockAlignmentToolbar,
    BlockControls,
    PanelColorSettings,
} = wp.blockEditor;

class PTAM_Filter_Posts extends Component {
    constructor() {
        super(...arguments);

        this.get_latest_data = this.get_latest_data.bind(this);
        this.get_latest_posts = this.get_latest_posts.bind(this);
        this.get_term_list = this.get_term_list.bind(this);

        this.state = {
            loading: true,
            postType: 'post',
            taxonomy: 'category',
            postTypeList: [],
            taxonomyList: [],
            termsList: [],
            userTaxonomies: [],
            userTerms: [],
        };

        this.get_latest_data();
    }

    get_latest_posts(object = {}) {
        this.setState({'loading': true});
        const props = jQuery.extend({}, this.props.attributes, object);
        let {postType, order, orderBy, taxonomy, avatarSize, imageType, imageTypeSize, term, postsToShow, imageCrop, linkColor} = props;

        axios.get(ptam_globals.rest_url + `ptam/v1/get_posts/${postType}/${order}/${orderBy}/${taxonomy}/${term}/${postsToShow}/${imageCrop}/${avatarSize}/${imageType}/${imageTypeSize}/${linkColor}`).then((response) => {
            // Now Set State
            this.setState({
                loading: false,
                latestPosts: response.data.posts,
                imageSizes: response.data.image_sizes,
                userTaxonomies: response.data.taxonomies,
                userTerms: response.data.terms
            });
        });
    }

    get_term_list(object = {}) {
        let termsList = [];
        const props = jQuery.extend({}, this.props.attributes, object);
        const {postType, taxonomy} = props;
        axios.get(ptam_globals.rest_url + `ptam/v1/get_terms/${taxonomy}/${postType}`).then((response) => {
            if (Object.keys(response.data).length > 0) {
                termsList.push({'value': 0, 'label': __('All', 'post-type-archive-mapping')});
                $.each(response.data, function (key, value) {
                    termsList.push({'value': value.term_id, 'label': value.name});
                });
            }
            this.setState({
                'loading': false,
                'termsList': termsList,
            });
        });
    }

    get_latest_data(object = {}) {
        this.setState({'loading': true});
        let latestPosts = [];
        let imageSizes = [];
        let postTypeList = [];
        let taxonomyList = [];
        let termsList = [];
        let userTaxonomies = [];
        let userTerms = [];
        const props = jQuery.extend({}, this.props.attributes, object);
        let {postType, order, orderBy, avatarSize, imageType, imageTypeSize, taxonomy, term, postsToShow, imageCrop, linkColor} = props;


        // Get Latest Posts and Chain Promises
        axios.get(ptam_globals.rest_url + `ptam/v1/get_posts/${postType}/${order}/${orderBy}/${taxonomy}/${term}/${postsToShow}/${imageCrop}/${avatarSize}/${imageType}/${imageTypeSize}/${linkColor}`).then((response) => {
            latestPosts = response.data.posts;
            imageSizes = response.data.image_sizes;
            userTaxonomies = response.data.taxonomies;

            // Get Post Types
            axios.get(ptam_globals.rest_url + 'wp/v2/types').then((response) => {
                $.each(response.data, function (key, value) {
                    if ('attachment' != key && 'wp_block' != key) {
                        postTypeList.push({'value': key, 'label': value.name});
                    }
                });

                // Get Terms
                axios.get(ptam_globals.rest_url + `ptam/v1/get_terms/${taxonomy}/${postType}`).then((response) => {
                    if (Object.keys(response.data).length > 0) {
                        termsList.push({'value': 0, 'label': __('All', 'post-type-archive-mapping')});
                        $.each(response.data, function (key, value) {
                            termsList.push({'value': value.term_id, 'label': value.name});
                        });
                    }

                    // Get Taxonomies
                    axios.get(ptam_globals.rest_url + `ptam/v1/get_taxonomies/${postType}`).then((response) => {
                        if (Object.keys(response.data).length > 0) {
                            taxonomyList.push({
                                'value': 'none',
                                'label': __('Select a Taxonomy', 'post-type-archive-mapping')
                            });
                            $.each(response.data, function (key, value) {
                                taxonomyList.push({'value': key, 'label': value.label});
                            });
                        }

                        // Now Set State
                        this.setState({
                            'loading': false,
                            'imageSizes': imageSizes,
                            'latestPosts': latestPosts,
                            'postTypeList': postTypeList,
                            'taxonomyList': taxonomyList,
                            'termsList': termsList,
                            'userTaxonomies': userTaxonomies,
                            'userTerms': userTerms,
                        });
                    });

                });
            });
        });
    }

    render() {
        let htmlToReactParser = new HtmlToReactParser();
        const {attributes, setAttributes} = this.props;
        const {postType, term, taxonomy, displayPostDate, displayPostDateBefore, displayPostExcerpt, displayPostAuthor,
            displayPostImage, displayPostLink, align, postLayout, columns, order, pagination, orderBy, postsToShow,
            readMoreText, imageLocation, taxonomyLocation, imageType, imageTypeSize, avatarSize, changeCapitilization,
            displayTaxonomies, trimWords, titleAlignment, imageAlignment, metaAlignment, contentAlignment, padding,
            border, borderRounded, borderColor, backgroundColor, titleColor, linkColor, contentColor, dateColor,
            continueReadingColor} = attributes;

        let userTaxonomies = this.state.userTaxonomies;
        let userTaxonomiesArray = [];
        for (var key in userTaxonomies) {
            userTaxonomiesArray.push({value: key, label: userTaxonomies[key].label});
        }
        let latestPosts = this.state.latestPosts;

        // Thumbnail options
        const imageLocationOptions = [
            {value: 'regular', label: __('Regular placement', 'post-type-archive-mapping')},
            {value: 'below_title', label: __('Image Below Title', 'post-type-archive-mapping')},
            {value: 'below_title_and_meta', label: __('Below title and post meta', 'post-type-archive-mapping')},
            {value: 'bottom', label: __('Image on bottom', 'post-type-archive-mapping')}
        ];
        let imageSizeOptions = [];
        let imageSizes = this.state.imageSizes;
        for (var key in imageSizes) {
            imageSizeOptions.push({value: key, label: key})
        }

        let imageDisplayOptionsTypes = [];
        imageDisplayOptionsTypes.push({label: __('Gravatar', 'post-type-archive-mapping'), value: 'gravatar'});
        imageDisplayOptionsTypes.push({label: __('Featured Image', 'post-type-archive-mapping'), value: 'regular'});

        const capitilization = changeCapitilization ? "ptam-text-lower-case" : '';

        const taxonomyLocationOptions = [
            {value: 'regular', label: __('Regular placement', 'post-type-archive-mapping')},
            {value: 'below_content', label: __('Below Content', 'post-type-archive-mapping')},
        ];

        const alignmentOptions = [
            {value: 'left', label: __('Left', 'post-type-archive-mapping')},
            {value: 'center', label: __('Center', 'post-type-archive-mapping')},
            {value: 'right', label: __('Right', 'post-type-archive-mapping')},
        ];

        const borderPaddingStyles = {
            padding: padding + 'px',
            border: border + 'px solid ' + borderColor,
            borderRadius: borderRounded + 'px',
            backgroundColor: backgroundColor,
        };


        const inspectorControls = (
            <InspectorControls>
                <PanelBody title={__('Filter Posts Settings', 'post-type-archive-mapping')}>

                </PanelBody>
                <PanelBody title={__('Options', 'post-type-archive-mapping')}>

                </PanelBody>
            </InspectorControls>
        );
        if (this.state.loading) {
            return (
                <Fragment>
                    {inspectorControls}
                    <Placeholder
                        icon="admin-post"
                        label={__('Filter Posts', 'post-type-archive-mapping')}
                    >
                        <Spinner/>
                    </Placeholder>
                </Fragment>
            )
        }
        const hasPosts = Array.isArray(latestPosts) && latestPosts.length;
        if (!hasPosts) {
            return (
                <Fragment>
                    {inspectorControls}
                    <Placeholder
                        icon="admin-post"
                        label={__('Filter Posts', 'post-type-archive-mapping')}
                    >
                        {!Array.isArray(latestPosts) ?
                            <Spinner/> :
                            __('No posts found.', 'post-type-archive-mapping')
                        }
                    </Placeholder>
                </Fragment>
            );
        }

        // Removing posts from display should be instant.
        const displayPosts = latestPosts.length > postsToShow ?
            latestPosts.slice(0, postsToShow) :
            latestPosts;

        const layoutControls = [];


        return (
            <Fragment>
                {inspectorControls}
                <BlockControls>
                    <BlockAlignmentToolbar
                        value={align}
                        onChange={(value) => {
                            if (undefined == value) {
                                value = 'wide';
                            }
                            setAttributes({align: value});
                        }}
                        controls={['center', 'wide']}
                    />
                </BlockControls>
                <div>
                    Filter posts will go here
                </div>
            </Fragment>
        );
    }
}

export default PTAM_Filter_Posts;
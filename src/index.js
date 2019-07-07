"use strict";

import {__} from "@wordpress/i18n";
import {CheckboxControl, PanelRow} from "@wordpress/components";
import {withSelect, withDispatch} from "@wordpress/data";
import {createElement, Fragment} from "@wordpress/element";
import {withState, compose} from "@wordpress/compose";
import {addFilter} from "@wordpress/hooks";

class HideFeaturedImage extends React.Component {
    render() {
        const {
            meta,
            updateHideFeaturedImage,
        } = this.props;

        return (
            <>
                <PanelRow>
                    <CheckboxControl
                        label={__('Display featured image in post lists only, hide on singular views.', 'conditionally-display-featured-image-on-singular-pages')}
                        checked={meta.cybocfi_hide_featured_image}
                        onChange={
                            (value) => {
                                this.setState({isChecked: value});
                                updateHideFeaturedImage(value, meta);
                            }
                        }
                    />
                </PanelRow>
            </>
        )
    }
}

const composedHideFeaturedImage = compose([
    withState((value) => value),
    withSelect((select) => {
        const currentMeta = select('core/editor').getCurrentPostAttribute('meta');
        const editedMeta = select('core/editor').getEditedPostAttribute('meta');
        return {
            meta: {...currentMeta, ...editedMeta},
        };
    }),
    withDispatch((dispatch) => ({
        updateHideFeaturedImage(value, meta) {
            value = value ? 'yes' : ''; // compatibility with classic editor
            meta = {
                ...meta,
                cybocfi_hide_featured_image: value,
            };
            dispatch('core/editor').editPost({meta});
        },
    })),
])(HideFeaturedImage);

const wrapPostFeaturedImage = function (OriginalComponent) {
    return function (props) {
        return (
            createElement(
                Fragment,
                {},
                null,
                createElement(
                    OriginalComponent,
                    props
                ),
                createElement(
                    composedHideFeaturedImage
                )
            )
        );
    }
};

addFilter(
    'editor.PostFeaturedImage',
    'cybocfi/addControl',
    wrapPostFeaturedImage
);
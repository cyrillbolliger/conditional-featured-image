"use strict";

import {CheckboxControl, PanelRow} from "@wordpress/components";
import {withSelect, withDispatch} from "@wordpress/data";
import {createElement, Fragment} from "@wordpress/element";
import {withState, compose} from "@wordpress/compose";
import {addFilter} from "@wordpress/hooks";

let dirty = false;

class HideFeaturedImage extends React.Component {
    render() {
        const {
            meta,
            isNew,
            updateHideFeaturedImage,
            getValue,
        } = this.props;

        return (
            <>
                <PanelRow>
                    <CheckboxControl
                        label={cybocfiL10n.featuredImageCheckboxLabel}
                        checked={getValue(isNew, meta)}
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
        const isEditedPostNew = select('core/editor').isEditedPostNew;
        return {
            meta: {...currentMeta, ...editedMeta},
            isNew: isEditedPostNew,
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
        getValue(isNew, meta) {
            if (isNew() && !dirty) {
                dirty = true;
                this.updateHideFeaturedImage(cybocfi.hideByDefault, meta);
                return cybocfi.hideByDefault;
            }
            return meta.cybocfi_hide_featured_image;
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
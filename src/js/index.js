"use strict";

import {CheckboxControl, PanelRow} from "@wordpress/components";
import {useSelect, useDispatch} from "@wordpress/data";
import {createElement, Fragment, useEffect} from "@wordpress/element";
import {addFilter} from "@wordpress/hooks";

// The panel unmounts while the sidebar shows the block tab. Remembering the
// posts that got the default keeps it from overwriting the user's choice when
// the panel mounts again.
const postIdsWithDefault = new Set();

function HideFeaturedImage() {
    const {postId, isNew, isHidden} = useSelect((select) => {
        const editor = select('core/editor');
        return {
            postId: editor.getCurrentPostId(),
            isNew: editor.isEditedPostNew(),
            // no meta in the REST API for post types without custom-fields
            isHidden: !!editor.getEditedPostAttribute('meta')?.cybocfi_hide_featured_image,
        };
    }, []);
    const {editPost} = useDispatch('core/editor');

    const updateHideFeaturedImage = (value) => {
        value = value ? 'yes' : ''; // compatibility with classic editor
        editPost({meta: {cybocfi_hide_featured_image: value}});
    };

    // handle_imports() stores the default on the auto-draft too, but misses a
    // filter added after the auto-draft was created.
    useEffect(() => {
        if (!isNew || postIdsWithDefault.has(postId)) {
            return;
        }
        postIdsWithDefault.add(postId);
        editPost({meta: {cybocfi_hide_featured_image: cybocfi.hideByDefault ? 'yes' : ''}});
    }, [postId, isNew, editPost]);

    return (
        <>
            <PanelRow>
                <CheckboxControl
                    label={cybocfiL10n.featuredImageCheckboxLabel}
                    checked={isHidden}
                    onChange={updateHideFeaturedImage}
                />
            </PanelRow>
        </>
    )
}

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
                    HideFeaturedImage
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
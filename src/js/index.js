"use strict";

import {CheckboxControl, PanelRow} from "@wordpress/components";
import {useSelect, useDispatch} from "@wordpress/data";
import {createElement, Fragment, useEffect} from "@wordpress/element";
import {addFilter} from "@wordpress/hooks";
import {registerPlugin} from "@wordpress/plugins";

// The plugin area mounts the component more than once (twice while the editor
// loads). Remembering the posts that got the default keeps it from
// overwriting the user's choice.
const postIdsWithDefault = new Set();

// A plugin, not part of the featured image panel: the panel mounts only while
// the sidebar shows it, and the user may save before that.
function ApplyHideByDefault() {
    const {postId, isNew, hasMeta} = useSelect((select) => {
        const editor = select('core/editor');
        return {
            postId: editor.getCurrentPostId(),
            isNew: editor.isEditedPostNew(),
            // no meta in the REST API for post types without custom-fields:
            // the edit would only mark the post as changed
            hasMeta: editor.getEditedPostAttribute('meta')?.cybocfi_hide_featured_image !== undefined,
        };
    }, []);
    const {editPost} = useDispatch('core/editor');

    // handle_imports() stores the default on the auto-draft too, but misses a
    // filter added after the auto-draft was created.
    useEffect(() => {
        if (!isNew || !hasMeta || postIdsWithDefault.has(postId)) {
            return;
        }
        postIdsWithDefault.add(postId);
        // not the user's edit: undo must not revert it
        editPost(
            {meta: {cybocfi_hide_featured_image: cybocfi.hideByDefault ? 'yes' : ''}},
            {undoIgnore: true}
        );
    }, [postId, isNew, hasMeta, editPost]);

    return null;
}

registerPlugin('cybocfi-hide-by-default', {render: ApplyHideByDefault});

function HideFeaturedImage() {
    const isHidden = useSelect((select) =>
        // no meta in the REST API for post types without custom-fields
        !!select('core/editor').getEditedPostAttribute('meta')?.cybocfi_hide_featured_image,
    []);
    const {editPost} = useDispatch('core/editor');

    const updateHideFeaturedImage = (value) => {
        value = value ? 'yes' : ''; // compatibility with classic editor
        editPost({meta: {cybocfi_hide_featured_image: value}});
    };

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
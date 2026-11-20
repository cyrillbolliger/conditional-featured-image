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

// undefined for post types without custom-fields: the REST API neither
// returns nor saves their meta
const getHideFlag = (editor) =>
    editor.getEditedPostAttribute('meta')?.cybocfi_hide_featured_image;

// A plugin, not part of the featured image panel: the panel mounts only while
// the sidebar shows it, and the user may save before that.
function ApplyHideByDefault() {
    const {postId, isNew, hasMeta, hasThumbnail} = useSelect((select) => {
        const editor = select('core/editor');
        const postType = select('core').getPostType(editor.getCurrentPostType());
        return {
            postId: editor.getCurrentPostId(),
            isNew: editor.isEditedPostNew(),
            // without the meta, the edit would only mark the post as changed
            hasMeta: getHideFlag(editor) !== undefined,
            // no featured image, no use for the flag. The post type record
            // loads asynchronously: false until then, so the effect waits.
            hasThumbnail: !!postType?.supports.thumbnail,
        };
    }, []);
    const {editPost} = useDispatch('core/editor');

    // handle_imports() stores the default on the auto-draft too, but misses a
    // filter added after the auto-draft was created.
    useEffect(() => {
        if (!isNew || !hasMeta || !hasThumbnail || postIdsWithDefault.has(postId)) {
            return;
        }
        postIdsWithDefault.add(postId);
        // not the user's edit: undo must not revert it
        editPost(
            {meta: {cybocfi_hide_featured_image: cybocfi.hideByDefault ? 'yes' : ''}},
            {undoIgnore: true}
        );
    }, [postId, isNew, hasMeta, hasThumbnail, editPost]);

    return null;
}

registerPlugin('cybocfi-hide-by-default', {render: ApplyHideByDefault});

function HideFeaturedImage() {
    const hideFlag = useSelect((select) => getHideFlag(select('core/editor')), []);
    const {editPost} = useDispatch('core/editor');

    // the edit would be dropped on save
    if (hideFlag === undefined) {
        return null;
    }

    const updateHideFeaturedImage = (value) => {
        value = value ? 'yes' : ''; // compatibility with classic editor
        editPost({meta: {cybocfi_hide_featured_image: value}});
    };

    return (
        <>
            <PanelRow>
                <CheckboxControl
                    label={cybocfiL10n.featuredImageCheckboxLabel}
                    checked={!!hideFlag}
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
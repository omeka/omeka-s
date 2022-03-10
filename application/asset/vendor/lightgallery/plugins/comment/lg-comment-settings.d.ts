export interface CommentStrings {
    toggleComments: string;
}
export interface CommentSettings {
    /**
     * Enable comment box
     */
    commentBox: boolean;
    /**
     * Enable facebook comment box
     */
    fbComments: boolean;
    /**
     * Enable disqus comment box
     */
    disqusComments: boolean;
    /**
     * Disqus comment config
     */
    disqusConfig: {
        title?: string;
        language: string;
    };
    /**
     * Facebook comments default markup
     */
    commentsMarkup: string;
    /**
     * Custom translation strings for aria-labels
     */
    commentPluginStrings: CommentStrings;
}
export declare const commentSettings: CommentSettings;

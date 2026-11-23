module.exports = {
    extends: ["stylelint-config-standard"],
    ignoreFiles: [
        "js/styles/cake.css",
    ],
    rules: {
        "color-hex-length": null,
        "color-function-notation": null,
        "alpha-value-notation": null,
        "no-descending-specificity": null,
        "selector-class-pattern": null,
        "keyframes-name-pattern": null,
        "media-feature-range-notation": null,
        "rule-empty-line-before": null,
        "declaration-block-no-duplicate-properties": [true, {
            ignore: ["consecutive-duplicates-with-different-values"]
        }]
    }
};

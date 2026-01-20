module.exports = {
    extends: ["stylelint-config-standard"],
    ignoreFiles: [
        "webroot/css/*.min.css",
        "webroot/css/normalize.min.css",
        "webroot/css/milligram.min.css",
        "webroot/css/cake.css",
        "webroot/css/fonts.css",
        "webroot/css/home.css"
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

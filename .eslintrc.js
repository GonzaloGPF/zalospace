// eslint-disable-next-line no-undef
module.exports = {
    extends: [
        // add more generic rulesets here, such as:
        'eslint:recommended',
        // 'plugin:vue/recommended',
        'plugin:vue/vue3-recommended',
    ],
    // globals: {
    //     $ref: 'readonly',
    // },
    parser: 'vue-eslint-parser',
    parserOptions: {
        'ecmaVersion': 2020,
        'sourceType': 'module',
        // 'ecmaFeatures': {
        //     'experimentalObjectRestSpread': true,
        // },
    },
    rules: {
        // override/add rules settings here, such as:
        'vue/no-unused-vars': 'error',
        'no-console': 'off',
        'vue/require-prop-types': 'off',
        'vue/no-v-html': 'off',
        'vue/html-indent': ['error', 2, {
            'attribute': 1,
            'closeBracket': 0,
            'alignAttributesVertically': true,
            'ignores': [],
        }],
        'vue/max-attributes-per-line': ['error', {
            'singleline': 4,
            'multiline': 1,
        }]
    },
};

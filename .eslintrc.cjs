module.exports = {
  env: {
    browser: true,
    es2021: true
  },
  extends: [
    'standard',
    'plugin:vue/vue3-essential'
  ],
  overrides: [
    {
      env: {
        node: true
      },
      files: [
        '.eslintrc.{js,cjs}'
      ],
      parserOptions: {
        sourceType: 'script'
      }
    }
  ],
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module'
  },
  plugins: [
    'vue'
  ],
  rules: {
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
  }
}

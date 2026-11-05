import globals from 'globals';
import pluginJs from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';

export default [
  {
    files: ['resources/js/**/*.{js,mjs,cjs,vue}']
  },
  {
    languageOptions: {
      globals: {
        ...globals.browser,
        // Injected by the @routes Blade directive
        route: 'readonly',
        Ziggy: 'readonly',
      },
    }
  },
  pluginJs.configs.recommended,
  ...pluginVue.configs['flat/essential'],
];
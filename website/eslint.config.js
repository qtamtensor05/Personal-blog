import js from '@eslint/js';
import eslintConfigPrettier from 'eslint-config-prettier';
import globals from 'globals';

export default [
    {
        ignores: ['node_modules/**', 'public/build/**', 'public/hot/**', 'storage/**', 'vendor/**'],
    },
    {
        files: ['resources/js/**/*.js', 'vite.config.js'],
        ...js.configs.recommended,
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.node,
            },
        },
    },
    eslintConfigPrettier,
];

// Required by sylius/test-application webpack.config.js:
// addEntry('plugin-shop-entry', '<plugin>/assets/shop/entrypoint.js').
//
// This plugin serves tarteaucitron from public/ via assets:install, not Webpack Encore.
// The file must exist or `yarn build` fails and admin/shop pages answer 500.

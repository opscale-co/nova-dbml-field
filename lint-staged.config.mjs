export default {
    '*.{php,js,vue,ts}': () => './vendor/bin/duster fix --dirty',
};

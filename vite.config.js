import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { viteStaticCopy } from 'vite-plugin-static-copy'

export default defineConfig({
    plugins: [
        laravel({
            input: [

                // Resources paths
                'resources/css/app.css', 
                'resources/sass/app.scss', 
                'resources/js/app.js',

                // Resources assets js file paths
                'resources/assets/js/advancedform',
                'resources/assets/js/apexcharts',
                'resources/assets/js/chart',
                'resources/assets/js/charts',
                'resources/assets/js/color-change',
                'resources/assets/js/construction',
                'resources/assets/js/crypto-datatable',
                'resources/assets/js/custom-switcher',
                'resources/assets/js/custom',
                'resources/assets/js/datatable',
                'resources/assets/js/echarts',
                'resources/assets/js/flot',
                'resources/assets/js/form-elements',
                'resources/assets/js/form-validation',
                'resources/assets/js/formeditor',
                'resources/assets/js/fullcalendar-rtl',
                'resources/assets/js/fullcalendar',
                'resources/assets/js/handlecounter',
                'resources/assets/js/index',
                'resources/assets/js/index1',
                'resources/assets/js/index2',
                'resources/assets/js/index3',
                'resources/assets/js/index4',
                'resources/assets/js/index5',
                'resources/assets/js/jvectormap',
                'resources/assets/js/landing',
                'resources/assets/js/left-menu',
                'resources/assets/js/map-leafleft',
                'resources/assets/js/mapelmaps',
                'resources/assets/js/modal',
                'resources/assets/js/morris',
                'resources/assets/js/nvd3',
                'resources/assets/js/owl-carousel',
                'resources/assets/js/picker',
                'resources/assets/js/popover',
                'resources/assets/js/range',
                'resources/assets/js/select2',
                'resources/assets/js/sticky',
                'resources/assets/js/stiky',
                'resources/assets/js/summernote',
                'resources/assets/js/sweet-alert',
                'resources/assets/js/table-data',
                'resources/assets/js/themeColors',
                'resources/assets/js/tooltip',
                'resources/assets/js/typehead',
                'resources/assets/js/widget',
                'resources/assets/switcher/js/switcher',

            ],
            refresh: true,
        }),
        viteStaticCopy({
            targets: [
              {
                src: ([
                'resources/assets/images/', 
                'resources/assets/plugins/',
                'resources/assets/iconfonts/',
                'resources/assets/js/form-wizard.js',
            ]),
                dest: 'assets/'
              }
            ]
        }),
        {
            name: 'blade',
            handleHotUpdate({ file, server }) {
                if (file.endsWith('.blade.php')) {
                    server.ws.send({
                        type: 'full-reload',
                        path: '*',
                    });
                }
            },
        }
    ],
    build: {
        chunkSizeWarningLimit: 1600,
    },
});


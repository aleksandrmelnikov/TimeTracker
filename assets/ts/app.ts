/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import $ from 'jquery';
import '@fortawesome/fontawesome-free/js/solid';
import '@fortawesome/fontawesome-free/js/brands';
import '@fortawesome/fontawesome-free/js/regular';
import '@fortawesome/fontawesome-free/js/fontawesome';

// any CSS you import will output into a single css file (app.css in this case)
import '../styles/app.scss';

// start the Stimulus application
import '../bootstrap';

import { Popover, Tooltip } from 'bootstrap';

$(document).ready(() => {
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=popover]'));
    const popoverList = popoverTriggerList.map((popoverTriggerElement) => {
        return new Popover(popoverTriggerElement);
    });

    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=tooltip]'))
    const tooltipList = tooltipTriggerList.map((tooltipTriggerElement) => {
        return new Tooltip(tooltipTriggerElement);
    });

    $('.js-clear-datetime').on('click', (event) => {
        const $parent = $(event.currentTarget).parent();

        $parent.find('input').val('');
    })

});

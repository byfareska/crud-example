// The panel in the "bootstrap5" theme of byfareska/crud: Bootstrap's CSS and JavaScript, nothing of its own.
import './stimulus_bootstrap.js';

// Bootstrap's data API (data-bs-toggle / data-bs-dismiss) is delegated on document, so it keeps working for markup
// swapped in by Turbo visits and live component re-renders. The panel's controllers open and close the same
// components through useBootstrap().
import Dropdown from 'bootstrap/js/dist/dropdown';
import Modal from 'bootstrap/js/dist/modal';
import Offcanvas from 'bootstrap/js/dist/offcanvas';
import { useBootstrap } from '@byfareska/crud';

useBootstrap({ Modal, Offcanvas, Dropdown });

import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.min.css';
import 'gridstack/dist/gridstack.min.css';
// The fields to search in (symfony/ux-autocomplete) in Bootstrap's look, dark mode included.
import 'tom-select/dist/css/tom-select.bootstrap5.css';
// The calendar of the date filters, in the panel's colours.
import 'vanilla-calendar-pro/styles/core.css';
import 'vanilla-calendar-pro/styles/months.css';
import '@byfareska/crud/styles/calendar.css';

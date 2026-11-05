// TinyMCE with what it loads, bundled: the editor of the fields of rich text (the description of a product). Imported
// only on a page with such a field (stimulus_bootstrap.js).
import tinymce from 'tinymce';
import 'tinymce/models/dom/model';
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/content/default/content.js';
import 'tinymce/skins/ui/oxide-dark/skin.js';
import 'tinymce/skins/ui/oxide-dark/content.js';
import 'tinymce/skins/content/dark/content.js';
import 'tinymce/plugins/autolink';
import 'tinymce/plugins/code';
import 'tinymce/plugins/image';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/table';
// The languages of the panel besides English (tinymce-i18n): the editor speaks that of the page, <html lang>.
import 'tinymce-i18n/langs8/pl.js';

export default tinymce;

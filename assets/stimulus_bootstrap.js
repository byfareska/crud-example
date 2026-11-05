import { startStimulusApp } from '@symfony/reprise/stimulus';
import { registerControllers, registerRichTextEditor } from '@byfareska/crud';
import { tinymceEditor } from '@byfareska/crud/editors/tinymce.js';
import Clipboard from './controllers/clipboard_controller.js';

const app = startStimulusApp();
// The controllers of byfareska/crud, under the names its templates use; the application's own go next to them.
registerControllers(app);
app.register('clipboard', Clipboard);
// The editor of the fields of rich text (byfareska_crud.rich_text): TinyMCE, loaded with the first such field.
registerRichTextEditor('tinymce', tinymceEditor(() => import('./tinymce.js').then((module) => module.default)));

import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();

// Désactiver les logs de debug en production
app.debug = false;

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

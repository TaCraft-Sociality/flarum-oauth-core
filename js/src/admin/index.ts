import app from 'flarum/admin/app';

export const EXTENSION_ID = 'tacraft-sociality-oauth-core';

app.initializers.add(EXTENSION_ID, () => {
  app.registry
    .for(EXTENSION_ID)
    .registerSetting({
      setting: `${EXTENSION_ID}.email_domain`,
      type: 'text',
      label: app.translator.trans(`${EXTENSION_ID}.admin.email_domain_label`),
      help: app.translator.trans(`${EXTENSION_ID}.admin.email_domain_help`),
      placeholder: 'fake.invalid',
    });
});

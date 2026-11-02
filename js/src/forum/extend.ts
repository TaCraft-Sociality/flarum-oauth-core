import { extend } from 'flarum/common/extend';
import type ItemList from 'flarum/common/utils/ItemList';

/**
 * Email notifications are disabled site-wide: everything is delivered in-site
 * only, so the "email" column is removed from the notification grid for every
 * account, whatever it logs in with.
 */
const oauthCoreExtender = {
  extend() {
    extend('flarum/forum/components/NotificationGrid', 'notificationMethods', function (this: any, items: ItemList<any>) {
      items.remove('email');
    });
  },
};

export default [oauthCoreExtender];

<? /* page added by CraftySpace */ ?>

<ul>
  <li class="leaf first"><?php print l(t('Summary'), 'user'); ?></li>
  <li class="leaf"><?php print l(t('My Checkouts'), 'user/checkouts'); ?></li>
  <li class="leaf"><?php print l(t('My Requests'), 'user/requests'); ?></li>
  <li class="leaf"><?php print l(t('My Fines'), 'user/fines'); ?></li>
  <li class="expanded last"><?php print l(t('My Library'), 'user/library'); ?>
    <ul class="menu">
      <li class="leaf first"><?php print l(t('Ratings'), 'user/library/ratings'); ?></li>
      <li class="leaf"><?php print l(t('Reviews'), 'user/library/reviews'); ?></li>
      <li class="leaf"><?php print l(t('Tags'), 'user/library/tags'); ?></li>
      <li class="leaf last"><?php print l(t('Searches'), 'user/library/searches'); ?></li>
    </ul>
  </li>
  <li class="leaf"><?php print l(t('Search Catalog'), 'catalog/search'); ?></li>
  <li class="leaf"><?php print l(t('Edit Account'), "/user/$uid/edit"); ?></li>
  <li class="leaf last"><?php print l(t('Log out'), 'logout'); ?></li>
</ul>
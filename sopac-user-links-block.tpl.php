<? /* page added by CraftySpace */ ?>

<ul>
  <li class="leaf first"><?php print l('Summary', 'user'); ?></li>
  <li class="leaf"><?php print l('My Checkouts', 'user/checkouts'); ?></li>
  <li class="leaf"><?php print l('My Holds', 'user/holds'); ?></li>
  <li class="leaf"><?php print l('My Fines', 'user/fines'); ?></li>
  <li class="expanded last"><?php print l('My Library', 'user/library'); ?>
    <ul class="menu">
      <li class="leaf first"><?php print l('Ratings', 'user/library/ratings'); ?></li>
      <li class="leaf"><?php print l('Reviews', 'user/library/reviews'); ?></li>
      <li class="leaf"><?php print l('Tags', 'user/library/tags'); ?></li>
      <li class="leaf last"><?php print l('Searches', 'user/library/searches'); ?></li>
    </ul>
  </li>
  <li class="leaf"><?php print l('Search Catalog', 'catalog/search'); ?></li>
  <li class="leaf"><?php print l('Edit Account', "/user/$uid/edit"); ?></li>
  <li class="leaf last"><?php print l('Log out', 'logout'); ?></li>
</ul>
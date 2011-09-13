<?php

function popularity_page() {
  print theme_header(FALSE);
  print <<<EOS
<h1>How do we calculate popularity?</h1>

<p>In Status Time Capsule, your popularity depends on:</p>

<ul>
  <li>average number of comments per status,</li>
  <li>average number of likes per status, and</li>
  <li>variance of these numbers among your statuses.</li>
</ul>

<p>Therefore, someone that consistently attracts comments and likes to all of his/her statuses may be ranked as more popular, compared to someone that has occasional popular statuses (with lots of likes and comments).</p>

<p>Technically, we calculate the lower bound of Wilson score confidence interval for a Bernoulli parameter for each user of our app. Then, we rank these lower bound values and derive the top most popular users as well as the percentage of people ranked below yours. For more explanation about the algorithm, see <a href="http://www.evanmiller.org/how-not-to-sort-by-average-rating.html">the article by Evan Miller</a>.</p>

EOS;
  print theme_links();
  print theme_footer();
  // Ugly error suppression
  @log_insert('popularity_hit');
}

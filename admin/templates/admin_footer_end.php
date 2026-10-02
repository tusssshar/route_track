
<div id="footer" >
  <div id="footer-wrap" class="container">
    <p class="copy_txt"><?php echo htmlspecialchars(company_settings::footer_text()); ?><?php
      $support = array_filter(array(company_settings::get("support_phone"), company_settings::get("support_email")));
      echo count($support) ? " &middot; Support: " . htmlspecialchars(implode(" / ", $support)) : ""; ?></p>
  </div>
</div>

</body>
</html>

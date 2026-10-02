-- Driver KYC: licence classes, experience, Aadhaar number, scanned licence / Aadhaar copies. Idempotent.
USE `RouteTrac_template_repository`;

ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `license_classes` varchar(50) NOT NULL DEFAULT '' COMMENT 'Comma list: MCWG, LMV, LMV-TR, HMV',
  ADD COLUMN IF NOT EXISTS `experience_years` tinyint(3) unsigned DEFAULT NULL COMMENT 'Years of driving experience',
  ADD COLUMN IF NOT EXISTS `aadhaar_no` varchar(12) NOT NULL DEFAULT '' COMMENT '12 digits, shown masked',
  ADD COLUMN IF NOT EXISTS `license_doc` varchar(255) NOT NULL DEFAULT '' COMMENT 'File under uploads/driver_docs (not web-readable)',
  ADD COLUMN IF NOT EXISTS `aadhaar_doc` varchar(255) NOT NULL DEFAULT '';

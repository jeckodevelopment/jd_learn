-- Protect native Joomla category references during permanent deletion.
-- Categories can still be trashed and restored through the Joomla lifecycle.
ALTER TABLE `#__jlms_courses` ADD CONSTRAINT `#__jlms_fk_course_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_pathways` ADD CONSTRAINT `#__jlms_fk_pathway_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_teachers` ADD CONSTRAINT `#__jlms_fk_teacher_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_questions` ADD CONSTRAINT `#__jlms_fk_question_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

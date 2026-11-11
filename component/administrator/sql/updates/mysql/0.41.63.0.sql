-- Protect native Joomla category references during permanent deletion.
-- Categories can still be trashed and restored through the Joomla lifecycle.
-- Joomla's native categories.id is signed INT; align retained catid columns before adding the FKs.
ALTER TABLE `#__jlms_courses` MODIFY `catid` INT NOT NULL;
ALTER TABLE `#__jlms_pathways` MODIFY `catid` INT NOT NULL;
ALTER TABLE `#__jlms_teachers` MODIFY `catid` INT NOT NULL;
ALTER TABLE `#__jlms_questions` MODIFY `catid` INT NOT NULL;
ALTER TABLE `#__jlms_courses` ADD CONSTRAINT `#__jlms_fk_course_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_pathways` ADD CONSTRAINT `#__jlms_fk_pathway_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_teachers` ADD CONSTRAINT `#__jlms_fk_teacher_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE `#__jlms_questions` ADD CONSTRAINT `#__jlms_fk_question_category` FOREIGN KEY (`catid`) REFERENCES `#__categories` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

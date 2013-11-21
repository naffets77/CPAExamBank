
DROP TABLE IF EXISTS `ciborium_dev`.`Question`;
CREATE  TABLE  `ciborium_dev`.`Question` (  `QuestionId` int( 11  )  unsigned NOT  NULL  AUTO_INCREMENT ,
                                            `QuestionClientId` varchar( 100  )  NOT  NULL  COMMENT  'VAhe''s reference id',
                                            `QuestionTypeId` int( 11  )  NOT  NULL DEFAULT  '1',
                                            `QuestionCategoryId` int( 11  )  NOT  NULL DEFAULT  '1',
                                            `DisplayName` varchar( 100  )  NOT  NULL  COMMENT  'In case we want to build a dropdown of questions to select from.',
                                            `DisplayText` text NOT  NULL  COMMENT  'Actual question that user will read. Can contain HTML based off of flag',
                                            `Explanation` text NOT  NULL ,
                                            `SectionTypeId` int( 11  )  NOT  NULL DEFAULT  '1',
                                            `Tags` varchar( 4000  )  NOT  NULL ,
                                            `QuestionClientImage` varchar( 100  )  NOT  NULL  COMMENT  'The image file associated with this question.',
                                            `HasHTMLInName` tinyint( 1  )  NOT  NULL DEFAULT  '0',
                                            `HasHTMLInText` tinyint( 1  )  NOT  NULL DEFAULT  '0',
                                            `IsSamplable` tinyint( 1  )  NOT  NULL DEFAULT  '0' COMMENT  'Can be used in a sample set',
                                            `IsApprovedForUse` tinyint( 1  )  NOT  NULL DEFAULT  '0' COMMENT  'When Vahe approves through UI',
                                            `IsActive` tinyint( 1  )  NOT  NULL  COMMENT  'Eligible for use on website',
                                            `IsDeprecated` tinyint( 1  )  NOT  NULL DEFAULT  '0',
                                            `DateLastModified` timestamp NOT  NULL  DEFAULT CURRENT_TIMESTAMP  ON  UPDATE  CURRENT_TIMESTAMP ,
                                            `LastModifiedBy` varchar( 100  )  NOT  NULL ,
                                            `DateCreated` datetime NOT  NULL ,
                                            `CreatedBy` varchar( 100  )  NOT  NULL ,
  PRIMARY  KEY (  `QuestionId`  )  ) ENGINE  = InnoDB  DEFAULT CHARSET  = latin1;

SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `ciborium_dev`.`Question` SELECT * FROM `ciborium_prod`.`Question`;



DROP TABLE IF EXISTS `ciborium_dev`.`QuestionToAnswers`;
CREATE  TABLE  `ciborium_dev`.`QuestionToAnswers` (  `QuestionToAnswersId` int( 11  )  unsigned NOT  NULL  AUTO_INCREMENT ,
                                                     `QuestionId` int( 11  )  NOT  NULL ,
                                                     `AnswerIndex` int( 11  )  NOT  NULL DEFAULT  '999' COMMENT  'sort order e.g. 1, 2, 3...',
                                                     `DisplayName` varchar( 100  )  NOT  NULL  COMMENT  'In case we want to build a dropdown of answers to select from.',
                                                     `DisplayText` varchar( 4000  )  NOT  NULL  COMMENT  'Actual answer that user will read. Can contain HTML based off of flag',
                                                     `IsAnswerToQuestion` tinyint( 1  )  NOT  NULL DEFAULT  '0',
                                                     `HasHTMLInName` tinyint( 1  )  NOT  NULL DEFAULT  '0',
                                                     `HasHTMLInText` tinyint( 1  )  NOT  NULL DEFAULT  '0',
                                                     `DateLastModified` timestamp NOT  NULL  DEFAULT CURRENT_TIMESTAMP  ON  UPDATE  CURRENT_TIMESTAMP ,
                                                     `LastModifiedBy` varchar( 100  )  NOT  NULL ,
                                                     `DateCreated` datetime NOT  NULL ,
                                                     `CreatedBy` varchar( 100  )  NOT  NULL ,
  PRIMARY  KEY (  `QuestionToAnswersId`  ) ,
  KEY  `QuestionId_Index` (  `QuestionId`  )  ) ENGINE  = InnoDB  DEFAULT CHARSET  = latin1;

SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `ciborium_dev`.`QuestionToAnswers` SELECT * FROM `ciborium_prod`.`QuestionToAnswers`;




DROP TABLE IF EXISTS `ciborium_dev`.`QuestionHistory`;
CREATE  TABLE  `ciborium_dev`.`QuestionHistory` (  `QuestionHistoryId` int( 11  )  NOT  NULL  AUTO_INCREMENT ,
                                                   `QuestionId` int( 11  )  NOT  NULL ,
                                                   `QuestionClientId` varchar( 100  )  NOT  NULL ,
                                                   `DisplayText` text NOT  NULL ,
                                                   `Explanation` text NOT  NULL ,
                                                   `QuestionClientImage` varchar( 100  )  NOT  NULL ,
                                                   `IsApprovedForUse` tinyint( 1  )  NOT  NULL ,
                                                   `IsActive` tinyint( 1  )  NOT  NULL ,
                                                   `LastModifiedBy` varchar( 200  )  NOT  NULL ,
                                                   `DateCreated` timestamp NOT  NULL  DEFAULT CURRENT_TIMESTAMP  ON  UPDATE  CURRENT_TIMESTAMP ,
  PRIMARY  KEY (  `QuestionHistoryId`  ) ,
  KEY  `QuestionId_Index` (  `QuestionId`  )  ) ENGINE  = InnoDB  DEFAULT CHARSET  = latin1;

SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `ciborium_dev`.`QuestionHistory` SELECT * FROM `ciborium_prod`.`QuestionHistory`;



DROP TABLE IF EXISTS `ciborium_dev`.`QuestionCategory`;
CREATE  TABLE  `ciborium_dev`.`QuestionCategory` (  `QuestionCategoryId` int( 11  )  NOT  NULL  AUTO_INCREMENT ,
                                                    `DisplayName` varchar( 100  )  NOT  NULL ,
                                                    `Description` varchar( 1000  )  NOT  NULL ,
                                                    `DateLastModified` timestamp NOT  NULL  DEFAULT CURRENT_TIMESTAMP  ON  UPDATE  CURRENT_TIMESTAMP ,
                                                    `LastModifiedBy` varchar( 100  )  NOT  NULL ,
                                                    `DateCreated` datetime NOT  NULL ,
                                                    `CreatedBy` varchar( 100  )  NOT  NULL ,
  PRIMARY  KEY (  `QuestionCategoryId`  )  ) ENGINE  = InnoDB  DEFAULT CHARSET  = latin1;

SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `ciborium_dev`.`QuestionCategory` SELECT * FROM `ciborium_prod`.`QuestionCategory`;
create table if not exists b_awz_admin_goption (
    `ID` int(18) NOT NULL AUTO_INCREMENT,
    `CODE` varchar(256) NOT NULL,
    `UP_DATE` datetime NOT NULL,
    `PRM` longtext NOT NULL,
    PRIMARY KEY (`ID`),
    unique IX_CODE (CODE)
);
CREATE TABLE IF NOT EXISTS `b_awz_admin_gens` (
    `ID` int(18) NOT NULL AUTO_INCREMENT,
    `NAME` varchar(256) NOT NULL,
    `ADM_LINK` varchar(256) NOT NULL,
    `ADD_DATE` datetime NOT NULL,
    `PRM` longtext NOT NULL,
    PRIMARY KEY (`ID`)
);
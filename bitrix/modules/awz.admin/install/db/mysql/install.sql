create table if not exists b_awz_admin_goption (
    `ID` int(18) NOT NULL AUTO_INCREMENT,
    `CODE` varchar(256) NOT NULL,
    `UP_DATE` datetime NOT NULL,
    `PRM` longtext NOT NULL,
    PRIMARY KEY (`ID`),
    unique IX_CODE (CODE)
);
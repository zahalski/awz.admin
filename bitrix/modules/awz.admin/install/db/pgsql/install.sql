CREATE TABLE b_awz_admin_goption (
    ID int GENERATED BY DEFAULT AS IDENTITY NOT NULL,
    CODE varchar(256) NOT NULL,
    UP_DATE timestamp,
    PRM text,
    PRIMARY KEY (ID)
);
CREATE TABLE `b_awz_admin_gens` (
    ID int GENERATED BY DEFAULT AS IDENTITY NOT NULL,
    NAME varchar(256) NOT NULL,
    ADM_LINK varchar(256) NOT NULL,
    ADD_DATE timestamp,
    PRM text,
    PRIMARY KEY (ID)
);
CREATE UNIQUE INDEX b_awz_admin_goption_code ON b_awz_admin_goption (code);
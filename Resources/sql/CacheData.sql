CREATE TABLE CacheData(
   cachekey      VARCHAR(32),
   cachedata     TEXT,
   createdate    INTEGER,
   CONSTRAINT    CacheDataPK PRIMARY KEY (cachekey)
);

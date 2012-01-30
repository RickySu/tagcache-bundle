CREATE TABLE TagRelation(
   tagkey      VARCHAR(32),
   cachekey    VARCHAR(32),
   CONSTRAINT    TagRelationPK PRIMARY KEY (tagkey,cachekey)
);
mbc-userAPI-registration
=========================

Message Broker - Consumer - Log user (email) creation to the UserAPI.

###Database Sanity Checks

* **Missing email** - All documents must have an `email` value. This includes users gathered by SMS which would have an `email` value of `1234567890@mobile` where 1234567890 is the `mobile` value.
```
db['mailchimp-users'].find({"created" : { $gte : new ISODate("2015-11-29T00:00:00Z") }, "email" :  {"$exists" : false }}).pretty()
```
* **Duplicate email** - There should only ever be one document per email address. The `mailchimp-users` collection is indexed by the `email` property.

```
db['mailchimp-users'].aggregate([
  { $group: {
    _id: { email: "$email" },
    uniqueIds: { $addToSet: "$_id" },
    count: { $sum: 1 }
  } },
  { $match: {
    count: { $gte: 2 }
  } },
  { $sort : { count : -1} },
  { $limit : 2 }
],
{
  allowDiskUse: true
}).pretty();
```

**NOTES**:
* This query requires a full pass of the collection for each duplicate found. Time and memory intensive, use with care.
* The `$limit` value manages the number of duplicates to find. Keeping this number low reduces the stress on the database.
* `allowDiskUse: true` gets past the Mongo 16Mb memory limitation.

* **Missing drupal_uid** - User documents need the `drupal_uid` value to uniquely identify a user in the Drupal application.
```
db['mailchimp-users'].find({"created" : { $gte : new ISODate("2015-11-29T00:00:00Z") }, "drupal_uid" :  {"$exists" : false }}).pretty()
```

* **Updates by ObjectID** - Duplicate email query above returns a list of `ObjectId`s that can be adjusted with:
```
db['mailchimp-users'].update(
   { "_id" : ObjectId("53fba76ea593a11d23caf977") },
   {
      email: "abc@xyz.com"
   }
)
```

 **Manage Mongo Documents** - Find, edit and delete of documents.
 ```
db['mailchimp-users'].find({email: "abc@xyz.com"}).pretty()

db['mailchimp-users'].find({"_id" : ObjectId("55027323a593a11d23d8e1dc")}).pretty()

db['mailchimp-users'].update(
   { "_id" : ObjectId("55027323a593a11d23d8e1dc") },
   {
      $set: {
        subscribed: 0
      }
   }
)

db['mailchimp-users'].remove({"_id" : ObjectId("55027323a593a11d23d8e1dd")})
```

#RecommendationsBundle

**A Built In Recommendations Engine with Symfony and MongoDB**

The recommendations are Item-Based, and use the Pearson Distance for matching similar items.

A cron job must be defined to run daily so the items affinities can be updated.

The service `andres_montanez_recommendations.recommendation` enables you to
- `registerItem`: register an item, you can specify a type (eg: movie), tags (eg: drama, action), and a namespace.
- `addAction`: allows to register a user interaction. You can specify the user, a verb (eg: rated), an item, a value, and a namespace.
- `getRecommendations`: you get recommendations for a givven user. You can narrow the results specifying a type, tag, and a namespace.

Example of an interaction: "User <Jon> <rated> the <movie Batman> with a value of <5>"

The namespace is if you want to use the engine on multiple sites but with only one instance, so recommendations will only be made for items registered on that namespace.
For the values of users, items, and tags, an Integer value is recommended.
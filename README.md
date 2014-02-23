#RecommendationsBundle

**A Built In Recommendations Engine with Symfony and MongoDB**

The recommendations are Item-Based, and use the Pearson Distance for matching similar items.

A cron job must be defined to run periodically so the items affinities can be updated.

The service `andres_montanez_recommendations.recommendation` enables you to
- `registerItem`: register an item, you can specify a type (eg: movie), tags (eg: drama, action), and a namespace.
- `addAction`: allows to register a user interaction. You can specify the user, a verb (eg: rated), an item, a value, and a namespace.
- `getRecommendations`: you get recommendations for a given user. You can narrow the results specifying a type, tag, and a namespace.

Example of an interaction: "User «<Jon» «rated» the «movie Batman» with a value of «5»"

The namespace is if you want to use the engine on multiple sites but with only one instance, so recommendations will only be made for items registered on that namespace.
For the values of users, items, and tags, an Integer value is recommended.

The main algorithms are based on the code of O'Reilly's Collective Intelligence.

The bundle has been tested with a datasets of:
- 100K ratings, 943 users, and 1682 items: ~4 minutes for similarities generations, under 2 seconds for getting user recommendations.
- 1 Million ratings, 6040 users, and 3883 items: ~90 minutes for similarities generations, under 2 seconds for getting user recommendations.

Datasets available at: http://grouplens.org/datasets/movielens/

I recommend building a service wrapper around this service so you can fit better your requirements, and also add as many cache layers as you want.
Have in mind that with large datasets, the results will vary slowly, it will take a lot of user interaction to change the similarities between two items, therefore altering the recommendation.
Regenerating the similarities once per week is a good place to start. Also caching the user's recommendations for 24-48 hours is also recommended, because this won't change often unless the user rates more items.

SELECT countries.name,cities.name, COUNT(city_id) AS records_count, SUM(clicks) AS click_sum,SUM(money) AS money_sum FROM countries 
JOIN (cities) ON (countries.id = cities.country_id )
JOIN (trafficcost) ON (cities.id = trafficcost.city_id)
GROUP BY trafficcost.city_id;

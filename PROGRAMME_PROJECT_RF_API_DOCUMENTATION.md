# Programme / Project / RF API Documentation

Base `/api/v1` + Sanctum + `X-Organization-Id`.

## Programmes
`GET/POST /programmes` · `GET/PUT/DELETE /programmes/{id}`  
`POST /programmes/{id}/documents` · `DELETE /programmes/{id}/documents/{document}`

## Projects
`GET/POST /projects` · `GET/PUT/DELETE /projects/{id}`  
`POST …/submit|return|approve|activate|archive`  
`PUT …/team` · `PUT …/geographic-units`  
`POST/DELETE …/documents`

## Results frameworks
`GET/POST /results-frameworks` · `GET/PUT /results-frameworks/{id}`  
`GET …/hierarchy` · `POST …/results`  
`POST …/submit|return|approve|activate|archive|version`  
`PUT /results/{id}` · `DELETE /results/{id}` · `POST /results/{id}/link-indicator`

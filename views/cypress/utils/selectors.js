export default {
    deleteClass: '[data-context="class"][data-action="deleteSharedStimulus"]',
    moveClass: '[id="media-move-to"][data-context="resource"][data-action="moveTo"]',
    moveConfirmSelector: 'button[data-control="ok"]',
    assetForm: 'form[action="/taoMediaManager/MediaManager/editInstance"]',
    assetClassForm: 'form[action="/taoMediaManager/MediaManager/editClassLabel"]',
    deleteConfirm: 'button[data-control="ok"]',
    root: '[data-uri="http://www.tao.lu/Ontologies/TAOMedia.rdf#Media"]',
    editClassLabelUrl: 'taoMediaManager/MediaManager/editClassLabel',
    editItemUrl: 'taoMediaManager/MediaManager/editItem',
    treeRenderUrl: 'taoMediaManager/MediaManager',
    addSubClassUrl: 'taoMediaManager/MediaManager/addSubClass',
    restResourceGetAll: 'tao/RestResource/getAll',
    resourceRelations: 'tao/ResourceRelations'
};
